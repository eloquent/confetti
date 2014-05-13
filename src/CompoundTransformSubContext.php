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
 * A data structure for the compound transform's sub-contexts.
 */
class CompoundTransformSubContext
{
    public $context;
    public $buffer = '';
    public $bufferSize = 1024;
}
