<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\Metrics;

use IWD\SymfonyAppCommon\Service\Metrics\EndpointDuration;
use IWD\SymfonyAppCommon\Service\Metrics\EndpointDurationRecorder;
use IWD\SymfonyAppCommon\Service\Metrics\EndpointStopwatch;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::CONTROLLER, method: 'onController', priority: -512)]
#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onResponse', priority: 1024)]
#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onException', priority: -256)]
#[AsEventListener(event: KernelEvents::TERMINATE, method: 'onTerminate', priority: -1024)]
final readonly class EndpointDurationSubscriber
{
    private const SECTION_ATTRIBUTE = '_endpoint_duration_section';

    public function __construct(
        private EndpointDurationRecorder $recorder,
        private EndpointStopwatch $stopwatch,
    ) {
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->shouldMeasure($request)) {
            return;
        }

        $key = 'action-' . spl_object_id($request);
        $request->attributes->set(self::SECTION_ATTRIBUTE, $key);
        $this->stopwatch->start($key);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->observe(request: $event->getRequest(), statusCode: $event->getResponse()->getStatusCode());
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || $event->hasResponse()) {
            return;
        }

        $throwable = $event->getThrowable();
        $statusCode = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;
        $this->observe(request: $event->getRequest(), statusCode: $statusCode);
    }

    public function onTerminate(): void
    {
        $this->stopwatch->reset();
    }

    private function shouldMeasure(Request $request): bool
    {
        if (Request::METHOD_OPTIONS === $request->getMethod()) {
            return false;
        }

        $route = (string) $request->attributes->get('_route', '');
        if ('metrics' === $route || str_starts_with($route, '_mcp_endpoint')) {
            return false;
        }

        return true;
    }

    private function observe(Request $request, int $statusCode): void
    {
        $key = $request->attributes->get(self::SECTION_ATTRIBUTE);
        if (!is_string($key)) {
            return;
        }

        $request->attributes->remove(self::SECTION_ATTRIBUTE);
        $seconds = $this->stopwatch->stop($key);
        if (null === $seconds) {
            return;
        }

        $name = (string) $request->attributes->get('_route', '');
        if ('' === $name) {
            $name = EndpointDuration::UNMATCHED;
        }

        $this->recorder->record(name: $name, kind: EndpointDuration::KIND_ACTION, statusCode: $statusCode, seconds: $seconds);
    }
}
