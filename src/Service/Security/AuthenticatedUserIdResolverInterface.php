<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Security;

use Symfony\Component\HttpFoundation\Request;

interface AuthenticatedUserIdResolverInterface
{
    public function tryResolveAuthenticatedUserId(Request $request): ?string;
}
