<?php

/*
 * This file is part of the Confetti package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Confetti;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * The interface implemented by transform streams.
 *
 * @event success
 */
interface TransformStreamInterface extends
    ReadableStreamInterface,
    WritableStreamInterface
{
    /**
     * Get the transform.
     *
     * @return TransformInterface The transform.
     */
    public function transform();
}
