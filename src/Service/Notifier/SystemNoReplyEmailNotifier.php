<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Notifier;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

readonly class SystemNoReplyEmailNotifier extends AbstractEmailNotifier implements SystemNoReplyEmailNotifierInterface
{
    public function __construct(
        MailerInterface $mailer,
        #[Autowire('%env(MAILER_NO_REPLY_FROM)%')]
        string $mailerFrom,
        #[Autowire('%env(resolve:PUBLIC_APP_URL)%')]
        string $publicAppUrl,
    ) {
        parent::__construct(
            mailer: $mailer,
            mailerFrom: $mailerFrom,
            publicAppUrl: $publicAppUrl,
        );
    }
}
