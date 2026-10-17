<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Contenir\Preview\Laminas\Mvc\Controller\Plugin\Preview as PreviewPlugin;
use Contenir\Preview\Laminas\Mvc\View\Helper\Preview as PreviewHelper;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\PreviewEntry;
use Contenir\Preview\PreviewResponse;
use Contenir\Preview\Replay\ReplayGuardInterface;
use Contenir\Preview\Resolver\ResolverRegistry;
use Contenir\Preview\Token\TokenVerifier;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * The services, view helper and controller plugin the module registers.
 *
 * @api
 */
final class ConfigProvider
{
    /**
     * @return array<string, array<string, string>>
     */
    public function dependencies(): array
    {
        return [
            'factories' => [
                PreviewOptions::class         => Factory\PreviewOptionsFactory::class,
                Factory\ClockFactory::SERVICE => Factory\ClockFactory::class,
                ReplayGuardInterface::class   => Factory\ReplayGuardFactory::class,
                ResolverRegistry::class       => Factory\ResolverRegistryFactory::class,
                TokenVerifier::class          => Factory\TokenVerifierFactory::class,
                PreviewEntry::class           => Factory\PreviewEntryFactory::class,
                PreviewResponse::class        => Factory\PreviewResponseFactory::class,
                PreviewContext::class         => InvokableFactory::class,
                PreviewListener::class        => Factory\PreviewListenerFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    public function __invoke(): array
    {
        return [
            'service_manager'    => $this->dependencies(),
            'view_helpers'       => [
                'aliases'   => ['preview' => PreviewHelper::class],
                'factories' => [PreviewHelper::class => Factory\PreviewHelperFactory::class],
            ],
            'controller_plugins' => [
                'aliases'   => ['preview' => PreviewPlugin::class],
                'factories' => [PreviewPlugin::class => Factory\PreviewHelperFactory::class],
            ],
        ];
    }
}
