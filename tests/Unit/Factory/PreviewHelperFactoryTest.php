<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Preview\Laminas\Mvc\Controller\Plugin\Preview as PreviewPlugin;
use Contenir\Preview\Laminas\Mvc\Factory\PreviewHelperFactory;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\Laminas\Mvc\View\Helper\Preview as PreviewHelper;
use Contenir\Preview\PreviewContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The view helper and the controller plugin over the application's one preview context.
 */
#[Group('unit')]
final class PreviewHelperFactoryTest extends TestCase
{
    #[Test]
    public function givesControllersThePreviewContext(): void
    {
        $context = new PreviewContext();

        $plugin = (new PreviewHelperFactory())(new ArrayContainer([
            PreviewContext::class => $context,
        ]), PreviewPlugin::class);

        $this->assertSame([PreviewPlugin::class, $context], [$plugin::class, $plugin()]);
    }

    #[Test]
    public function givesTemplatesThePreviewContext(): void
    {
        $context = new PreviewContext();

        $helper = (new PreviewHelperFactory())(new ArrayContainer([
            PreviewContext::class => $context,
        ]), PreviewHelper::class);

        $this->assertSame([PreviewHelper::class, $context], [$helper::class, $helper()]);
    }
}
