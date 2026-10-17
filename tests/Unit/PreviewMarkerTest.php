<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\PreviewMarker;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The visible badge a previewed page carries.
 */
#[Group('unit')]
final class PreviewMarkerTest extends TestCase
{
    #[Test]
    public function leavesAPageWithoutABodyAsItIs(): void
    {
        $this->assertSame('{"title":"Spa Rituals"}', PreviewMarker::mark('{"title":"Spa Rituals"}'));
    }

    #[Test]
    public function placesTheBadgeBeforeTheLastClosingBodyTag(): void
    {
        $this->assertSame(
            '<p>&lt;/body&gt;</p><script>"</body>"</script>' . PreviewMarker::HTML . '</BODY></html>',
            PreviewMarker::mark('<p>&lt;/body&gt;</p><script>"</body>"</script></BODY></html>'),
        );
    }
}
