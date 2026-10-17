<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site;

use Contenir\Preview\Laminas\Mvc\Controller\Plugin\Preview;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Shows the page stored at a slug, with a preview's values laid over it.
 *
 * @method Preview preview()
 */
final class PageController extends AbstractActionController
{
    public function indexAction(): ViewModel
    {
        $slug   = (string) $this->params()->fromRoute('slug');
        $stored = ['resource_id' => '101', 'slug' => $slug, 'title' => 'Stored title'];
        $page   = $this->plugin('preview')()->overlay('site.page.home', ['resource_id' => '101'], $stored);

        return (new ViewModel(['title' => $page['title']]))->setTemplate('site/page');
    }
}
