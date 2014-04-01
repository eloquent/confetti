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
 * The interface implemented by compound stream transforms.
 */
interface CompoundTransformInterface extends TransformInterface
{
    /**
     * Get the sequence of transforms that are applied to incoming data.
     *
     * @return array<TransformInterface> The transforms.
     */
    public function transforms();
}
