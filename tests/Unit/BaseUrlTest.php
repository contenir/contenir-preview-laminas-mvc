<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\BaseUrl;
use Laminas\Http\PhpEnvironment\Request as EnvironmentRequest;
use Laminas\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Where the application lives below its host.
 */
#[Group('unit')]
final class BaseUrlTest extends TestCase
{
    #[Test]
    public function isNoneForARequestWithoutAnEnvironment(): void
    {
        $this->assertSame('', BaseUrl::of(new Request()));
    }

    #[Test]
    public function isTheSubdirectoryTheApplicationRunsIn(): void
    {
        $request = new EnvironmentRequest();
        $request->setBaseUrl('/site');

        $this->assertSame('/site', BaseUrl::of($request));
    }
}
