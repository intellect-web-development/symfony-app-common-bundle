<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Notifier;

interface SystemNoReplyEmailNotifierInterface
{
    public function sendHtml(
        string $to,
        string $subject,
        string $html,
    ): void;

    public function renderBaseHtmlMessage(
        string $to,
        string $title,
        string $subtitle,
        ?string $buttonLink,
        ?string $buttonLabel,
        ?string $welcomeText,
        string $info,
        string $mistake,
        bool $needUnsubscribeLink,
    ): string;
}
