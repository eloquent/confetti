<?php

/*
 * This file is part of the Confetti package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Confetti\Exception;

use Exception;

/**
 * The stream is closed.
 */
final class StreamClosedException extends Exception
{
    /**
     * Construct a new stream closed exception.
     *
     * @param Exception|null $cause The cause, if available.
     */
    public function __construct(Exception $cause = null)
    {
        parent::__construct('The stream is closed.', 0, $cause);
    }
}
