<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Messenger;

use IWD\SymfonyAppCommon\Messages\Platform\IntegrationSystemEventMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class PlatformEnvelopeSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        /** @var array{type?: string, integrationSystemCode?: string, payload?: array<string, string>} $data */
        $data = json_decode($encodedEnvelope['body'], true, 512, JSON_THROW_ON_ERROR);

        return new Envelope(new IntegrationSystemEventMessage(
            type: (string) ($data['type'] ?? ''),
            integrationSystemCode: (string) ($data['integrationSystemCode'] ?? ''),
            payload: $data['payload'] ?? [],
        ));
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        if (!$message instanceof IntegrationSystemEventMessage) {
            throw new \InvalidArgumentException('Platform transport accepts IntegrationSystemEventMessage only.');
        }

        return [
            'body' => json_encode([
                'type' => $message->type,
                'integrationSystemCode' => $message->integrationSystemCode,
                'payload' => $message->payload,
            ], JSON_THROW_ON_ERROR),
            'headers' => ['type' => $message->type],
        ];
    }
}
