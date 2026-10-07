<?php

declare(strict_types=1);

namespace app\exceptions;

/**
 * A business rule failed. The API maps this family to 4xx, not 500.
 */
class DomainException extends \RuntimeException
{
}
