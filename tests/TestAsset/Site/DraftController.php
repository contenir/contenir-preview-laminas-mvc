<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Shows a page not saved yet, which no route reaches, from the preview's values.
 */
final class DraftController extends AbstractActionController
{
    public function indexAction(): ViewModel
    {
        $draft = $this->plugin('preview')()->unsaved('site.page.draft') ?? ['title' => 'No draft'];

        return (new ViewModel(['title' => $draft['title']]))->setTemplate('site/page');
    }
}
