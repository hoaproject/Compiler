<?php

namespace Hoa\Compiler\Exception;

use LogicException;

/**
 * It probably points to some internal issue of the Hoa Compiler library.
 * Regardless source of the bug, try to report about this exception to the library maintainers.
 * Even if bug is yours, this exception must not happen.
 */
final class InternalError extends LogicException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
