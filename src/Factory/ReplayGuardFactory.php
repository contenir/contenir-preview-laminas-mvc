<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Replay\CacheReplayGuard;
use Contenir\Preview\Replay\FileReplayGuard;
use Contenir\Preview\Replay\ReplayGuardInterface;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

use function sys_get_temp_dir;

/**
 * Where accepted tokens are remembered: the configured PSR-16 cache, which every server shares,
 * or files in a directory (the system's temporary directory unless one is configured).
 */
final class ReplayGuardFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container): ReplayGuardInterface
    {
        $options = Service::get($container, PreviewOptions::class);
        $clock   = Service::get($container, ClockInterface::class, ClockFactory::SERVICE);

        return null === $options->replay->cache
            ? new FileReplayGuard($options->replay->directory ?? sys_get_temp_dir(), $clock)
            : new CacheReplayGuard(Service::get($container, CacheInterface::class, $options->replay->cache), $clock);
    }
}
