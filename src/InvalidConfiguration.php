<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use InvalidArgumentException;

/**
 * The application's `contenir_preview` configuration cannot be used as it stands.
 */
final class InvalidConfiguration extends InvalidArgumentException {}
