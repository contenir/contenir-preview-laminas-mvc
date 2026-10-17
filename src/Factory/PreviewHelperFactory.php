<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Factory;

use Contenir\Preview\Laminas\Mvc\Controller\Plugin\Preview as PreviewPlugin;
use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\View\Helper\Preview as PreviewHelper;
use Contenir\Preview\PreviewContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * The view helper and the controller plugin, both over the application's one preview context.
 */
final class PreviewHelperFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function __invoke(ContainerInterface $container, string $requestedName): PreviewHelper|PreviewPlugin
    {
        $context = Service::get($container, PreviewContext::class);

        return PreviewPlugin::class === $requestedName ? new PreviewPlugin($context) : new PreviewHelper($context);
    }
}
