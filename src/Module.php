<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerExceptionInterface;

use function is_array;

/**
 * Answers the CMS's previews once the application has a `contenir_preview` configuration; without
 * one it registers its services and does nothing else.
 *
 * @api
 */
final class Module
{
    /**
     * @return array<string, array<string, array<string, string>>>
     */
    public function getConfig(): array
    {
        return (new ConfigProvider())();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfiguration
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $application = $event->getApplication();
        $services    = $application->getServiceManager();
        /** @var mixed $config */
        $config = $services->get('config');
        if (! is_array($config) || null === ($config['contenir_preview'] ?? null)) {
            return;
        }

        Factory\Service::get($services, PreviewListener::class)->attach($application->getEventManager());
    }
}
