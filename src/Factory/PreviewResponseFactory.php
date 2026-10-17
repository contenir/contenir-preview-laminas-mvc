<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\PreviewResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class PreviewResponseFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container): PreviewResponse
    {
        return new PreviewResponse(Service::get($container, PreviewOptions::class)->config);
    }
}
