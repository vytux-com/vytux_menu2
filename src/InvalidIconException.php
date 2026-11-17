<?php

declare(strict_types=1);

namespace Vytux\WebtreesModules\VytuxMenu2;

use RuntimeException;

/**
 * Thrown when an uploaded icon is rejected.  The code is one of the REASON_* constants.
 */
final class InvalidIconException extends RuntimeException
{
    public const int REASON_EMPTY        = 1;
    public const int REASON_TOO_LARGE    = 2;
    public const int REASON_NOT_AN_IMAGE = 3;
    public const int REASON_DIMENSIONS   = 4;
}
