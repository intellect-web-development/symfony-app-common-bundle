<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\Metrics;

use IWD\SymfonyAppCommon\Service\Metrics\EndpointDuration;
use IWD\SymfonyAppCommon\Service\Metrics\EndpointDurationRecorder;
use IWD\SymfonyAppCommon\Service\Metrics\EndpointStopwatch;
use Mcp\Event\ErrorEvent;
use Mcp\Event\RequestEvent;
use Mcp\Event\ResponseEvent;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: RequestEvent::class, method: 'onRequest')]
#[AsEventListener(event: ResponseEvent::class, method: 'onResponse')]
#[AsEventListener(event: ErrorEvent::class, method: 'onError')]
final readonly class McpToolDurationSubscriber
{
    public function __construct(
        private EndpointDurationRecorder $recorder,
        private EndpointStopwatch $stopwatch,
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request instanceof CallToolRequest) {
            return;
        }

        $this->stopwatch->start($this->key($request));
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request instanceof CallToolRequest) {
            return;
        }

        $result = $event->getResponse()->result;
        $statusCode = $result instanceof CallToolResult && $result->isError ? 400 : 200;
        $this->observe(request: $request, statusCode: $statusCode);
    }

    public function onError(ErrorEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request instanceof CallToolRequest) {
            return;
        }

        $this->observe(request: $request, statusCode: 500);
    }

    private function observe(CallToolRequest $request, int $statusCode): void
    {
        $seconds = $this->stopwatch->stop($this->key($request));
        if (null === $seconds) {
            return;
        }

        $this->recorder->record(name: $request->name, kind: EndpointDuration::KIND_MCP, statusCode: $statusCode, seconds: $seconds);
    }

    private function key(CallToolRequest $request): string
    {
        return 'mcp-' . $request->getId();
    }
}
