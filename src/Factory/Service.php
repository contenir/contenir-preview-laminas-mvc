<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

/**
 * A service from the container, checked to be the type its user needs.
 *
 * @internal
 */
final class Service
{
    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration When the service is something else
     */
    public static function get(ContainerInterface $container, string $type, ?string $name = null): object
    {
        $name ??= $type;
        /** @var mixed $service */
        $service = $container->get($name);

        return $service instanceof $type
            ? $service
            : throw new InvalidConfiguration(sprintf('The service %s is not a %s.', $name, $type));
    }
}
