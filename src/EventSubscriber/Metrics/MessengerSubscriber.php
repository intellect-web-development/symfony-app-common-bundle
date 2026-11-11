<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\Metrics;

use IWD\SymfonyAppCommon\Service\Metrics\AdapterInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;
use Symfony\Component\Messenger\Event\WorkerRateLimitedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

#[AsEventListener(event: SendMessageToTransportsEvent::class, method: 'onSendMessageToTransportsEvent')]
#[AsEventListener(event: WorkerMessageFailedEvent::class, method: 'onWorkerMessageFailedEvent')]
#[AsEventListener(event: WorkerMessageHandledEvent::class, method: 'onWorkerMessageHandledEvent')]
#[AsEventListener(event: WorkerMessageReceivedEvent::class, method: 'onWorkerMessageReceivedEvent')]
#[AsEventListener(event: WorkerMessageRetriedEvent::class, method: 'onWorkerMessageRetriedEvent')]
#[AsEventListener(event: WorkerRateLimitedEvent::class, method: 'onWorkerRateLimitedEvent')]
#[AsEventListener(event: WorkerStartedEvent::class, method: 'onWorkerStartedEvent')]
#[AsEventListener(event: WorkerStoppedEvent::class, method: 'onWorkerStoppedEvent')]
//#[AsEventListener(event: WorkerRunningEvent::class, method: 'onWorkerRunningEvent')]
readonly class MessengerSubscriber
{
    public function __construct(
        private AdapterInterface $adapter,
    ) {
    }

    /**
     * Отправляется при запуске воркера.
     *
     * Позволяет выполнять действия при запуске воркера.
     */
    public function onWorkerStartedEvent(WorkerStartedEvent $event): void
    {
        $this->adapter->createCounter(
            name: 'messenger_worker_started',
            help: 'Messenger Worker was started'
        )->inc();
    }

    /**
     * Отправляется, когда сообщение было получено от транспортного средства, но перед отправкой на шину.
     *
     * Позволяет выполнять действия при получении сообщения, такие как проверка его перед обработкой.
     * Аналог мидлвара, запускается ровно перед ним.
     */
    public function onWorkerMessageReceivedEvent(WorkerMessageReceivedEvent $event): void
    {
        $this->adapter->createCounter(
            name: 'messenger_worker_received_event',
            help: 'Messenger Worker received event'
        )->inc();
    }

    /**
     * Отправляется после того, как сообщение было получено от транспортного средства и успешно обработано.
     *
     * Обычно следует за WorkerMessageReceivedEvent, в случае успеха
     *
     * Позволяет выполнять действия после успешной обработки сообщения, такие как логирование или обновление статуса сообщения.
     */
    public function onWorkerMessageHandledEvent(WorkerMessageHandledEvent $event): void
    {
        $this->adapter->createCounter(
            name: 'messenger_worker_success_message_handled',
            help: 'Messenger Worker success message handled'
        )->inc();
    }

    /**
     * Отправляется, когда было получено сообщение от транспортного средства и произошла ошибка обработки.
     *
     * Позволяет выполнять действия при возникновении ошибки обработки, такие как логирование или повторная попытка.
     */
    public function onWorkerMessageFailedEvent(WorkerMessageFailedEvent $event): void
    {
        //        $messageBody = $this->serializer->serialize($event->getEnvelope()->getMessage(), 'json');
        $stamps = $event->getEnvelope()->all();

        /** @var BusNameStamp|null $busNameStamp */
        $busNameStamp = $this->getFirstStamp(stamps: $stamps, stampFqcn: BusNameStamp::class);
        /** @var ReceivedStamp|null $firstReceivedStamp */
        $firstReceivedStamp = $this->getFirstStamp(stamps: $stamps, stampFqcn: ReceivedStamp::class);

        //            /** @var AmqpReceivedStamp|null $firstAmqpReceivedStamp */
        //            $firstAmqpReceivedStamp = $this->getFirstStamp($stamps, AmqpReceivedStamp::class);
        /** @var AmqpReceivedStamp|null $endReceivedStamp */
        $endReceivedStamp = $this->getLastStamp(stamps: $stamps, stampFqcn: AmqpReceivedStamp::class);
        //            $firstReceivedDatetime = new DateTimeImmutable('@' . $firstAmqpReceivedStamp->getAmqpEnvelope()->getTimestamp());
        //
        //            /** @var RedeliveryStamp|null $endRedeliveryStamp */
        //            $endRedeliveryStamp = $this->getLastStamp($stamps, RedeliveryStamp::class);

        $queueName = $endReceivedStamp?->getQueueName();
        $transportName = $firstReceivedStamp?->getTransportName();
        $messageName = (static function () use ($event) {
            $fqcn = $event->getEnvelope()->getMessage()::class;
            $explode = explode('\\', $fqcn);

            return end($explode);
        })();

        //            $now = new DateTimeImmutable();
        //            $retriesAttempt = ($endRedeliveryStamp?->getRetryCount() ?? 0) + 1;
        //            $minutesFromFirstTouch = $this->computeMinutesForInterval($now, $firstReceivedDatetime);

        // todo: записывать метрики такого рода(как ниже) в summary
        //                    'retries_attempt',
        //                    'minutes_from_first_touch',

        //            $this->logger->info(
        //                <<<TEMPLATE
        //                    Шина: {$busNameStamp?->getBusName()}
        //                    Сообщение: $messageName
        //                    Очередь: $queueName
        //                    Транспорт: $transportName
        //                    Тело сообщения: $messageBody
        //                    Время первой обработки сообщения: {$firstReceivedDatetime->format('Y-m-d H:i:s')}
        //                    Время предыдущей попытки обработки: {$endRedeliveryStamp?->getRedeliveredAt()->format('Y-m-d H:i:s')}
        //                    Количество совершенных попыток: $retriesAttempt
        //                    Текущая попытка {$now->format('Y-m-d H:i:s')}
        //                    Времени прошло с первой попытки обработать: {$minutesFromFirstTouch} (в минутах)
        //                    TEMPLATE,
        //            );
        if ($event->willRetry()) {
            $this->adapter->createCounter(
                name: 'messenger_worker_retry_failed_message_handled',
                help: 'Messenger Worker retry failed message handled',
                labels: [
                    'bus_name',
                    'message_name',
                    'transport_name',
                    'queue_name',
                ]
            )->inc([
                (string) $busNameStamp?->getBusName(),
                (string) $messageName,
                (string) $transportName,
                (string) $queueName,
            ]);
        } else {
            $this->adapter->createCounter(
                name: 'messenger_worker_first_failed_message_handled',
                help: 'Messenger Worker first failed message handled',
                labels: [
                    'bus_name',
                    'message_name',
                    'transport_name',
                    'queue_name',
                ]
            )->inc([
                (string) $busNameStamp?->getBusName(),
                (string) $messageName,
                (string) $transportName,
                (string) $queueName,
            ]);
        }
    }

    /**
     * Отправляется, когда воркер был остановлен.
     *
     * Позволяет выполнять действия после остановки работника.
     */
    public function onWorkerStoppedEvent(WorkerStoppedEvent $event): void
    {
        $this->adapter->createCounter(
            name: 'messenger_worker_stopped',
            help: 'Messenger worker stopped'
        )->inc();
    }

    /**
     * Событие отправляется до отправки сообщения транспортному устройству.
     *
     * Событие отправляется только в том случае, если сообщение действительно будет
     * отправлено хотя бы одному транспортному устройству. Если сообщение отправлено
     * нескольким транспортным устройствам, сообщение отправляется только один раз.
     * Это сообщение отправляется только в первый раз, когда сообщение
     * отправляется на транспорт, но не отправляется, если попытка повторена.
     *
     * Позволяет вносить изменения в сообщение или его метаданные перед отправкой.
     */
    public function onSendMessageToTransportsEvent(SendMessageToTransportsEvent $event): void
    {
        $this->adapter->createCounter(
            name: 'messenger_send_message_to_transports',
            help: 'Messenger send message to transports'
        )->inc();
    }

    /**
     * Отправляется после того, как сообщение было отправлено для повторной попытки.
     *
     * Позволяет отслеживать повторные попытки и принимать меры в зависимости от количества повторных попыток.
     */
    public function onWorkerMessageRetriedEvent(WorkerMessageRetriedEvent $event): void
    {
        $this->adapter->createCounter(
            name: 'messenger_worker_message_retried',
            help: 'Messenger Worker message retried'
        )->inc();
    }

    /**
     * @template TStamp of StampInterface
     *
     * @param class-string<TStamp> $stampFqcn
     *
     * @return TStamp|null
     */
    private function getLastStamp(array $stamps, string $stampFqcn): ?StampInterface
    {
        return isset($stamps[$stampFqcn]) ? end($stamps[$stampFqcn]) : null;
    }

    /**
     * @template TStamp of StampInterface
     *
     * @param class-string<TStamp> $stampFqcn
     *
     * @return TStamp|null
     */
    private function getFirstStamp(array $stamps, string $stampFqcn): ?StampInterface
    {
        return isset($stamps[$stampFqcn]) ? current($stamps[$stampFqcn]) : null;
    }

    //    private function computeMinutesForInterval(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd): int
    //    {
    //        $interval = $dateEnd->diff($dateStart);
    //
    //        return $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
    //    }

    /**
     * Отправляется после того, как работник обработал сообщение или вообще не получил сообщение.
     */
    public function onWorkerRunningEvent(WorkerRunningEvent $event): void
    {
    }
}
