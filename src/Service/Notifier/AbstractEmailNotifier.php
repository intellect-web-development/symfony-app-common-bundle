<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Notifier;

use DateTimeImmutable;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

abstract readonly class AbstractEmailNotifier
{
    private const string EMAIL_FONT_STACK = 'Arial, Helvetica, sans-serif';
    private const string COLOR_PAGE_BG = '#e9eef5';
    private const string COLOR_CARD_BG = '#ffffff';
    private const string COLOR_BORDER = '#dfe3ea';
    private const string COLOR_BORDER_SOFT = '#eceff3';
    private const string COLOR_TEXT = '#3d4654';
    private const string COLOR_TEXT_MUTED = '#6a7585';
    private const string COLOR_PRIMARY = '#4a86c7';
    private const string COLOR_SURFACE = '#f4f7fc';
    private const string COLOR_INFO_ACCENT = '#7eb3e5';
    private const string COLOR_BUTTON_TEXT = '#ffffff';
    private const string CARD_BORDER_RADIUS = '14px';
    private const string BUTTON_BORDER_RADIUS = '8px';

    public function __construct(
        protected MailerInterface $mailer,
        protected string $mailerFrom,
        protected string $publicAppUrl,
    ) {
    }

    public function sendHtml(
        string $to,
        string $subject,
        string $html,
    ): void {
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($to)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

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
    ): string {
        $currentYear = (new DateTimeImmutable())->format('Y');
        $buttonHtml = $this->renderEmailButtonHtml(buttonLink: $buttonLink, buttonLabel: $buttonLabel);
        $welcomeHtml = $this->renderEmailWelcomeHtml($welcomeText);
        $unsubscribeHtml = $this->renderEmailUnsubscribeHtml(to: $to, needUnsubscribeLink: $needUnsubscribeLink);
        $font = self::EMAIL_FONT_STACK;
        $pageBg = self::COLOR_PAGE_BG;
        $cardBg = self::COLOR_CARD_BG;
        $border = self::COLOR_BORDER;
        $borderSoft = self::COLOR_BORDER_SOFT;
        $text = self::COLOR_TEXT;
        $textMuted = self::COLOR_TEXT_MUTED;
        $primary = self::COLOR_PRIMARY;
        $surface = self::COLOR_SURFACE;
        $infoAccent = self::COLOR_INFO_ACCENT;
        $cardRadius = self::CARD_BORDER_RADIUS;

        return <<<BODY
            <!DOCTYPE html>
            <html lang="ru" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="color-scheme" content="light">
                <meta name="supported-color-schemes" content="light">
                <title>{$title}</title>
                <!--[if mso]>
                <noscript>
                    <xml>
                        <o:OfficeDocumentSettings>
                            <o:PixelsPerInch>96</o:PixelsPerInch>
                        </o:OfficeDocumentSettings>
                    </xml>
                </noscript>
                <![endif]-->
            </head>
            <body>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{$pageBg}" style="width: 100%; background-color: {$pageBg};">
                    <tr>
                        <td align="center" bgcolor="{$pageBg}" style="padding: 24px 12px; background-color: {$pageBg};">
                            <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="{$cardBg}" style="width: 100%; max-width: 600px; background-color: {$cardBg}; border: 1px solid {$border}; border-radius: {$cardRadius}; overflow: hidden;">
                                <tr>
                                    <td bgcolor="{$cardBg}" style="padding: 28px 32px 22px; background-color: {$cardBg}; border-bottom: 1px solid {$borderSoft};">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 0 0 12px 0; font-family: {$font}; font-size: 14px; font-weight: bold; line-height: 18px; color: {$primary};">
                                                    Skeleton
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 0 0 8px 0; font-family: {$font}; font-size: 24px; font-weight: bold; line-height: 30px; color: {$text};">
                                                    {$title}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 0; font-family: {$font}; font-size: 15px; line-height: 22px; color: {$textMuted};">
                                                    {$subtitle}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="{$cardBg}" style="padding: 32px; background-color: {$cardBg};">
                                        {$welcomeHtml}
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 0 0 28px 0; font-family: {$font}; font-size: 15px; line-height: 24px; color: {$text};">
                                                    {$info}
                                                </td>
                                            </tr>
                                        </table>
                                        {$buttonHtml}
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td bgcolor="{$surface}" style="padding: 16px 18px; background-color: {$surface}; border: 1px solid {$border}; border-left: 3px solid {$infoAccent}; font-family: {$font}; font-size: 14px; line-height: 22px; color: {$textMuted};">
                                                    {$mistake}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" bgcolor="{$surface}" style="padding: 24px 32px; background-color: {$surface}; border-top: 1px solid {$borderSoft};">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" style="padding: 0 0 6px 0; font-family: {$font}; font-size: 13px; line-height: 20px; color: {$textMuted};">
                                                    © 2025–{$currentYear} Skeleton
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding: 0; font-family: {$font}; font-size: 13px; line-height: 20px; color: {$textMuted};">
                                                    <a href="{$this->publicAppUrl}" style="color: {$primary}; text-decoration: underline;" target="_blank">Приложение</a>
                                                    {$unsubscribeHtml}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            BODY;
    }

    private function renderEmailButtonHtml(?string $buttonLink, ?string $buttonLabel): string
    {
        if (null === $buttonLink || null === $buttonLabel) {
            return '';
        }

        $font = self::EMAIL_FONT_STACK;
        $primary = self::COLOR_PRIMARY;
        $buttonText = self::COLOR_BUTTON_TEXT;
        $buttonRadius = self::BUTTON_BORDER_RADIUS;

        return <<<BODY
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" style="padding: 0 0 28px 0;">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{$buttonLink}" style="height:48px;v-text-anchor:middle;width:280px;" arcsize="17%" stroke="f" fillcolor="{$primary}">
                            <w:anchorlock/>
                            <center style="color:{$buttonText};font-family:{$font};font-size:15px;font-weight:bold;">
                                {$buttonLabel}
                            </center>
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-->
                        <table cellpadding="0" cellspacing="0" border="0" align="center">
                            <tr>
                                <td align="center" bgcolor="{$primary}" style="background-color: {$primary}; border-radius: {$buttonRadius};">
                                    <a href="{$buttonLink}" target="_blank" style="display: block; min-width: 220px; padding: 14px 28px; background-color: {$primary}; border-radius: {$buttonRadius}; font-family: {$font}; font-size: 15px; font-weight: bold; line-height: 20px; color: {$buttonText}; text-decoration: none; text-align: center;">{$buttonLabel}</a>
                                </td>
                            </tr>
                        </table>
                        <!--<![endif]-->
                    </td>
                </tr>
            </table>
            BODY;
    }

    private function renderEmailWelcomeHtml(?string $welcomeText): string
    {
        if (null === $welcomeText) {
            return '';
        }

        $font = self::EMAIL_FONT_STACK;
        $text = self::COLOR_TEXT;

        return <<<BODY
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="padding: 0 0 20px 0; font-family: {$font}; font-size: 15px; line-height: 24px; color: {$text};">
                        {$welcomeText}
                    </td>
                </tr>
            </table>
            BODY;
    }

    private function renderEmailUnsubscribeHtml(string $to, bool $needUnsubscribeLink): string
    {
        if (!$needUnsubscribeLink) {
            return '';
        }

        $unsubscribeUrl = $this->generateUnsubscribeUrl(email: $to);
        if (null === $unsubscribeUrl) {
            return '';
        }

        $textMuted = self::COLOR_TEXT_MUTED;

        return <<<BODY
            ·
            <a href="{$unsubscribeUrl}" style="color: {$textMuted}; text-decoration: underline;" target="_blank">
                Отписаться от рассылок
            </a>
            BODY;
    }

    public function generateUnsubscribeUrl(string $email): ?string
    {
        return null;
    }
}
