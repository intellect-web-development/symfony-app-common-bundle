<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon;

use IWD\SymfonyAppCommon\Container\CompillerPass\RateLimitingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SymfonyAppCommonBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new RateLimitingPass());
    }
}
