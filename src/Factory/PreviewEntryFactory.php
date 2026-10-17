<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\PreviewEntry;
use Contenir\Preview\Resolver\ResolverRegistry;
use Contenir\Preview\Token\TokenVerifier;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class PreviewEntryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container): PreviewEntry
    {
        return new PreviewEntry(
            Service::get($container, TokenVerifier::class),
            Service::get($container, ResolverRegistry::class),
            Service::get($container, PreviewContext::class),
        );
    }
}
