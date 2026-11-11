<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Exception\Http;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class AccessDeniedHttpException extends HttpException
{
    public function __construct(string $message = 'Доступ запрещен.')
    {
        parent::__construct(
            statusCode: 403,
            message: $message,
        );
    }
}
