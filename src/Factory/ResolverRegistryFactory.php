<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Resolver\PathResolver;
use Contenir\Preview\Resolver\PreviewResolverInterface;
use Contenir\Preview\Resolver\ResolverRegistry;
use Contenir\Preview\Resolver\RouteResolver;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function str_starts_with;

/**
 * Each section's resolver as configured: a path pattern (beginning with a slash), a named route
 * with its parameters, or a resolver service from the container.
 */
final class ResolverRegistryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container): ResolverRegistry
    {
        $resolvers = [];
        foreach (Service::get($container, PreviewOptions::class)->resolvers as $section => $spec) {
            $resolvers[$section] = match (true) {
                is_array($spec)             => new RouteResolver($spec['route'], $spec['params'] ?? []),
                str_starts_with($spec, '/') => new PathResolver($spec),
                default                     => Service::get($container, PreviewResolverInterface::class, $spec),
            };
        }

        return new ResolverRegistry($resolvers);
    }
}
