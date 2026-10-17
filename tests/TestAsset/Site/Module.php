<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site;

use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * A small site: pages at their slug, and a draft page only a preview reaches.
 */
final class Module
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'router'          => [
                'routes' => [
                    'page' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'       => '/:slug',
                            'constraints' => ['slug' => '[a-z0-9-]+'],
                            'defaults'    => ['controller' => PageController::class, 'action' => 'index'],
                        ],
                    ],
                ],
            ],
            'controllers'     => ['factories' => [
                PageController::class  => InvokableFactory::class,
                DraftController::class => InvokableFactory::class,
            ]],
            'service_manager' => ['factories' => [DraftResolver::class => InvokableFactory::class]],
            'view_manager'    => [
                'display_exceptions'       => true,
                'display_not_found_reason' => true,
                'not_found_template'       => 'error/404',
                'exception_template'       => 'error/index',
                'template_map'             => [
                    'layout/layout' => __DIR__ . '/view/layout.phtml',
                    'site/page'     => __DIR__ . '/view/page.phtml',
                    'error/404'     => __DIR__ . '/view/404.phtml',
                    'error/index'   => __DIR__ . '/view/error.phtml',
                ],
            ],
        ];
    }
}
