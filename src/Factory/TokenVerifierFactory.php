<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Replay\ReplayGuardInterface;
use Contenir\Preview\Token\TokenVerifier;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class TokenVerifierFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container): TokenVerifier
    {
        return new TokenVerifier(
            Service::get($container, PreviewOptions::class)->config,
            Service::get($container, ClockInterface::class, ClockFactory::SERVICE),
            Service::get($container, ReplayGuardInterface::class),
        );
    }
}
