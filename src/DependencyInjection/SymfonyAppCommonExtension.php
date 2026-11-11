<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\DependencyInjection;

use IWD\SymfonyAppCommon\EventSubscriber\Metrics\McpToolDurationSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SymfonyAppCommonExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        if (class_exists(\Mcp\Event\RequestEvent::class)) {
            $container->register(McpToolDurationSubscriber::class)
                ->setAutowired(true)
                ->setAutoconfigured(true);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $translationsDir = dirname(__DIR__) . '/Resources/translations';
        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [$translationsDir],
                ],
            ]);
        }

        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig('doctrine', [
                'dbal' => [
                    'types' => [
                        'common_measurement' => 'IWD\SymfonyAppCommon\Doctrine\Type\MeasurementType',
                    ],
                ],
            ]);
        }
    }
}
