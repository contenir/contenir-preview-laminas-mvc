<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site;

use Contenir\Preview\PreviewRecord;
use Contenir\Preview\Resolver\PreviewResolverInterface;
use Contenir\Preview\Resolver\PreviewTarget;
use Override;

/**
 * Sends every draft to the controller that shows drafts.
 */
final class DraftResolver implements PreviewResolverInterface
{
    #[Override]
    public function resolve(PreviewRecord $record): PreviewTarget
    {
        return PreviewTarget::handler(DraftController::class, ['action' => 'index']);
    }
}
