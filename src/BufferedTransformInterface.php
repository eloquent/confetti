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

/**
 * The interface implemented by stream transforms that use buffering.
 */
interface BufferedTransformInterface extends TransformInterface
{
    /**
     * Get the buffer size.
     *
     * This method is used to determine how much input is typically required
     * before output can be produced. This can provide performance benefits by
     * avoiding excessive method calls.
     *
     * @return integer The buffer size.
     */
    public function bufferSize();
}
