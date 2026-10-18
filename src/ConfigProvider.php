<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Contenir\Preview\ConfigProvider as CoreConfigProvider;
use Contenir\Preview\Laminas\Mvc\Controller\Plugin\Preview as PreviewPlugin;
use Contenir\Preview\Laminas\Mvc\View\Helper\Preview as PreviewHelper;

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
        $core = (new CoreConfigProvider())->getDependencies();

        return [
            'factories' => [
                ...$core['factories'],
                PreviewListener::class => Factory\PreviewListenerFactory::class,
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
