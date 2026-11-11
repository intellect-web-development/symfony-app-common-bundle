<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\SendTestEmail;

use IWD\SymfonyEntryContract\Attribute\CliContract;
use IWD\SymfonyAppCommon\Container\AppCliCommand;
use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:common:send-test-email',
    description: 'Отправить тестовый email'
)]
#[CliContract(class: InputContract::class)]
class SendTestEmailCommand extends AppCliCommand
{
    public function __construct(
        CliContractResolver $cliContractResolver,
        LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        #[Autowire('%env(MAILER_NO_REPLY_FROM)%')]
        private readonly string $mailerFrom,
    ) {
        parent::__construct(cliContractResolver: $cliContractResolver, logger: $logger);
    }

    /**
     * @param InputContract $inputContract
     */
    protected function handle(InputContractInterface $inputContract): int
    {
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to('shaman.alexander.erin@yandex.ru')
            ->subject('Код активации скриннера')
            ->text('Добро пожаловать в систему! Ваш код активации: 1475');

        $this->mailer->send($email);

        return self::SUCCESS;
    }
}
