<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Container\CompillerPass;

use IWD\SymfonyAppCommon\EventSubscriber\RateLimitter\ApplyRateLimitingListener;
use IWD\SymfonyAppCommon\RateLimiter\Attribute\RateLimiting;
use LogicException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RateLimitingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ApplyRateLimitingListener::class)) {
            throw new LogicException(sprintf('Can not configure non-existent service %s', ApplyRateLimitingListener::class));
        }

        $taggedServices = $container->findTaggedServiceIds('controller.service_arguments');
        /** @var Definition[] $serviceDefinitions */
        $serviceDefinitions = array_map(static fn (string $id) => $container->getDefinition($id), array_keys($taggedServices));

        $rateLimiterClassMap = [];

        foreach ($serviceDefinitions as $serviceDefinition) {
            $controllerClass = $serviceDefinition->getClass();
            $reflClass = $container->getReflectionClass($controllerClass);

            /**
             * @psalm-suppress InvalidArgument
             */
            foreach ($reflClass?->getMethods(ReflectionMethod::IS_PUBLIC | ~ReflectionMethod::IS_STATIC) ?? [] as $reflMethod) {
                $attributes = $reflMethod->getAttributes(RateLimiting::class);
                if ([] !== $attributes) {
                    [$attribute] = $attributes;

                    $serviceKey = sprintf('limiter.%s', $attribute->newInstance()->limiter);
                    if (!$container->hasDefinition($serviceKey)) {
                        throw new RuntimeException(sprintf('Service %s not found', $serviceKey));
                    }

                    $classMapKey = sprintf('%s::%s', $serviceDefinition->getClass(), $reflMethod->getName());
                    $rateLimiterClassMap[$classMapKey] = $container->getDefinition($serviceKey);
                }
            }
        }

        $container->getDefinition(ApplyRateLimitingListener::class)->setArgument('$rateLimiterClassMap', $rateLimiterClassMap);
    }
}
