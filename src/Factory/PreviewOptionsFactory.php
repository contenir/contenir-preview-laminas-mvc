<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;

final class PreviewOptionsFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container): PreviewOptions
    {
        /** @var mixed $config */
        $config = $container->get('config');

        return PreviewOptions::fromAppConfig(is_array($config) ? $config : []);
    }
}
