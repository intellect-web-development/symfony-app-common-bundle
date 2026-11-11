<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\Exception;

use IWD\SymfonyAppCommon\Exception\Http\AccessDeniedHttpException;
use IWD\SymfonyAppCommon\Exception\Domain\DomainException;
use IWD\SymfonyAppCommon\Exception\Http\ToManyRequestException;
use IWD\SymfonyAppCommon\Service\Metrics\AdapterInterface;
use Exception;
use IWD\SymfonyDoctrineSearch\Exception\SymfonyDoctrineSearchException;
use IWD\SymfonyEntryContract\Exception\DeserializePayloadToInputContractException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;
use IWD\SymfonyEntryContract\Dto\Output\ApiFormatter;
use IWD\SymfonyEntryContract\Exception\SymfonyEntryContractBundleException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'logException', priority: 2)]
#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onFormatterException', priority: 1)]
readonly class HttpExceptionSubscriber
{
    public const string INTERNAL_ERROR_KEY = 'app.http.internal_error';
    public const string BAD_REQUEST_KEY = 'app.http.bad_request';

    public function __construct(
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
        private AdapterInterface $metrics,
        private TranslatorInterface $translator,
        #[Autowire('%env(APP_ENV)%')]
        private string $env,
        #[Autowire('%env(LOCAL_TEST)%')]
        private bool $debug,
    ) {
    }

    public function logException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $this->metrics->createCounter(
            name: 'error:http',
            help: 'error in http'
        )->inc();
        try {
            throw $exception;
        } catch (DeserializePayloadToInputContractException $exception) {
            $this->logger->warning(
                sprintf(
                    'Failed deserialize request to InputContract. Payload: %s, Error: %s',
                    json_encode($exception->getPayload(), JSON_THROW_ON_ERROR),
                    $exception->getPrevious()?->getMessage()
                )
            );
        } catch (DomainException $exception) {
            $this->logger->warning($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function onFormatterException(ExceptionEvent $event): void
    {
        $format = (string) $event->getRequest()->attributes->get('_format', 'json');
        $locale = $this->resolveLocale($event->getRequest());
        $event->allowCustomResponseCode();

        $exception = $event->getThrowable();
        if ($exception instanceof AccessDeniedException) {
            /** @var Request|null $request */
            $request = $exception->getSubject();
            if (null !== $request && Request::METHOD_GET === $request->getMethod()) {
                return;
            }
            $this->respond(
                event: $event,
                format: $format,
                status: Response::HTTP_UNAUTHORIZED,
                messages: [$this->translateUserMessage(message: $exception->getMessage(), locale: $locale)],
            );

            return;
        }

        try {
            $previous = $exception->getPrevious();
            if (!empty($previous) && is_subclass_of($previous, DomainException::class)) {
                throw $previous;
            }

            throw $exception;
        } catch (ToManyRequestException $exception) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->add($exception->getHeaders());
            $response->setContent(
                $this->serializer->serialize(
                    $this->toApiFormat(exception: $exception, code: Response::HTTP_TOO_MANY_REQUESTS, locale: $locale),
                    $format
                )
            );
            $response->headers->add(['Content-Type' => 'application/' . $format]);
            $event->setResponse($response);
        } catch (AccessDeniedHttpException $exception) {
            $this->respond(
                event: $event,
                format: $format,
                status: Response::HTTP_FORBIDDEN,
                messages: [$this->translateUserMessage(message: $exception->getMessage(), locale: $locale)],
            );
        } catch (HttpException $exception) {
            $response = new Response();
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->add($exception->getHeaders());
            $response->setContent(
                $this->serializer->serialize(
                    $this->toApiFormat(exception: $exception, code: $exception->getStatusCode(), locale: $locale),
                    $format
                )
            );
            $response->headers->add(['Content-Type' => 'application/' . $format]);
            $event->setResponse($response);
        } catch (DomainException|SymfonyEntryContractBundleException|SymfonyDoctrineSearchException $exception) {
            $this->respond(
                event: $event,
                format: $format,
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                messages: $this->extractMessages(exception: $exception, locale: $locale),
            );
        } catch (\Doctrine\DBAL\Exception $exception) {
            if ($this->shouldPassThroughDebug($format)) {
                return;
            }
            $this->respond(
                event: $event,
                format: $format,
                status: Response::HTTP_BAD_REQUEST,
                messages: [$this->translator->trans(self::BAD_REQUEST_KEY, [], null, $locale)],
            );
        } catch (Throwable $exception) {
            if ($this->shouldPassThroughDebug($format)) {
                return;
            }
            $this->respond(
                event: $event,
                format: $format,
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                messages: [$this->translator->trans(self::INTERNAL_ERROR_KEY, [], null, $locale)],
            );
        }
    }

    /**
     * HTML в dev/test+debug — Symfony profiler. JSON/API всегда конверт, без internals в messages.
     */
    private function shouldPassThroughDebug(string $format): bool
    {
        if ($this->isApiFormat($format)) {
            return false;
        }

        return $this->isDev() || ($this->isTest() && $this->debug);
    }

    private function isApiFormat(string $format): bool
    {
        return in_array($format, ['json', 'xml'], true);
    }

    /**
     * @param list<string> $messages
     */
    private function respond(ExceptionEvent $event, string $format, int $status, array $messages): void
    {
        $response = new Response();
        $response->setContent(
            $this->serializer->serialize(
                ApiFormatter::prepare(null, $status, $messages),
                $format
            )
        );
        $response->setStatusCode($status);
        $response->headers->add(['Content-Type' => 'application/' . $format]);
        $event->setResponse($response);
    }

    protected function isDev(): bool
    {
        return 'dev' === $this->env;
    }

    protected function isTest(): bool
    {
        return 'test' === $this->env;
    }

    protected function isProd(): bool
    {
        return 'prod' === $this->env;
    }

    protected function toApiFormat(Exception $exception, ?int $code, string $locale): array
    {
        return ApiFormatter::prepare(
            [],
            $code ?? $exception->getCode(),
            $this->extractMessages(exception: $exception, locale: $locale)
        );
    }

    /**
     * @return list<string>
     */
    private function extractMessages(Exception $exception, string $locale): array
    {
        $errors = json_validate($exception->getMessage())
            ? json_decode($exception->getMessage(), true, 512, JSON_THROW_ON_ERROR)
            : [$exception->getMessage()]
        ;

        if (!is_array($errors)) {
            return [$this->translateUserMessage(message: (string) $errors, locale: $locale)];
        }

        $messages = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                foreach ($error as $item) {
                    $messages[] = $this->translateUserMessage(message: (string) $item, locale: $locale);
                }
                continue;
            }
            $messages[] = $this->translateUserMessage(message: (string) $error, locale: $locale);
        }

        return $messages;
    }

    private function translateUserMessage(string $message, string $locale): string
    {
        if ('' === $message) {
            return $this->translator->trans(self::INTERNAL_ERROR_KEY, [], null, $locale);
        }

        return $this->translator->trans($message, [], null, $locale);
    }

    /**
     * Locale из Accept-Language, без set_locale_from_accept_language (он вешает _locale на все маршруты).
     */
    private function resolveLocale(Request $request): string
    {
        return $request->getPreferredLanguage(['ru', 'en']) ?? 'ru';
    }
}
