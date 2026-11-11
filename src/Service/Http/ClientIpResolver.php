<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Http;

use Symfony\Component\HttpFoundation\Request;

final readonly class ClientIpResolver
{
    public function resolve(Request $request): string
    {
        return (string) ($request->getClientIp() ?? '');
    }
}
