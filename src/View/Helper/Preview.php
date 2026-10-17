<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\View\Helper;

use Contenir\Preview\PreviewContext;

/**
 * `$this->preview()` in a template: the request's preview context, to tell a preview from a visit
 * (`$this->preview()->isActive()`) or lay its values over a record the template loads itself.
 */
final readonly class Preview
{
    public function __construct(
        private PreviewContext $context,
    ) {}

    public function __invoke(): PreviewContext
    {
        return $this->context;
    }
}
