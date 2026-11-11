<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Exception;

use Throwable;

class LogExceptionFormatter
{
    public const string KEY_CLASS            = 'class';
    public const string KEY_MESSAGE          = 'message';
    public const string KEY_CODE             = 'code';
    public const string KEY_FILE             = 'file';
    public const string KEY_LINE             = 'line';
    public const string KEY_PREVIOUS_MESSAGE = 'previousMessage';
    public const string KEY_TRACE            = 'trace';
    public const string KEY_PREVIOUS         = 'previous';
    public const string KEY_CONTEXT          = 'context';

    public static function toArray(Throwable $exception, bool $isFormatPrevious = true): array
    {
        $data = [
            self::KEY_CLASS            => $exception::class,
            self::KEY_MESSAGE          => $exception->getMessage(),
            self::KEY_FILE             => $exception->getFile(),
            self::KEY_LINE             => $exception->getLine(),
            self::KEY_CODE             => $exception->getCode(),
            self::KEY_PREVIOUS_MESSAGE => null !== $exception->getPrevious() ? $exception->getPrevious()->getMessage() : null,
            self::KEY_TRACE            => $exception->getTraceAsString(),
        ];

        if ($isFormatPrevious && null !== $exception->getPrevious()) {
            $data[self::KEY_PREVIOUS] = self::toArray(exception: $exception->getPrevious(), isFormatPrevious: false);
        }

        if ($exception instanceof WithContextExceptionInterface) {
            $data[self::KEY_CONTEXT] = $exception->getContext();
        }

        return $data;
    }

    public static function toArrayWithParams(Throwable $exception, array $additionalParams = []): array
    {
        return array_merge(self::toArray($exception), $additionalParams);
    }

    public static function toArrayCompact(Throwable $exception, array $additionalParams = []): array
    {
        $result = array_merge(self::toArray(exception: $exception, isFormatPrevious: false), $additionalParams);
        unset($result[self::KEY_TRACE]);

        return $result;
    }
}
