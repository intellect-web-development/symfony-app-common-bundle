<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Exception\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\RateLimiter\RateLimit;

class ToManyRequestException extends HttpException
{
    public function __construct(RateLimit $limit)
    {
        parent::__construct(
            statusCode: Response::HTTP_TOO_MANY_REQUESTS,
            message: 'Too many requests',
            headers: [
                'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
                'X-RateLimit-Limit' => $limit->getLimit(),
            ],
        );
    }
}
