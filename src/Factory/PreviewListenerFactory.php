<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Container\InvalidConfiguration;
use Contenir\Preview\Container\PreviewOptions;
use Contenir\Preview\Container\Service;
use Contenir\Preview\Laminas\Mvc\PreviewListener;
use Contenir\Preview\Laminas\Mvc\TargetMatcher;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\PreviewEntry;
use Contenir\Preview\PreviewResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class PreviewListenerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container): PreviewListener
    {
        return new PreviewListener(
            Service::get($container, PreviewEntry::class),
            new TargetMatcher(),
            Service::get($container, PreviewContext::class),
            Service::get($container, PreviewResponse::class),
            Service::get($container, PreviewOptions::class)->marker,
        );
    }
}
