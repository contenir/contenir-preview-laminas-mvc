<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Controller\Plugin;

use Contenir\Preview\PreviewContext;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * `$this->preview()` in a controller: the request's preview context, to lay its values over the
 * record the controller loads (`$this->preview()->overlay('site.page.home', $keys, $row)`) or take
 * the values of one not saved yet.
 */
final class Preview extends AbstractPlugin
{
    public function __construct(
        private readonly PreviewContext $context,
    ) {}

    public function __invoke(): PreviewContext
    {
        return $this->context;
    }
}
