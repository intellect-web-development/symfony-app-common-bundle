<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\Metrics;

use IWD\SymfonyAppCommon\Service\Metrics\AdapterInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onSendMetrics', priority: 100)]
class EndpointCallSubscriber
{
    public function __construct(
        private readonly AdapterInterface $adapter
    ) {
    }

    public function onSendMetrics(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $counter = $this->adapter->createCounter(
            name: 'endpoint_call',
            help: 'Endpoint calls counter',
            labels: [
                'name',
                'code',
                'method',
            ]
        );

        $counter->inc([
            $request->attributes->get('_route'),
            (string) $response->getStatusCode(),
            $request->getMethod(),
        ]);
    }
}
