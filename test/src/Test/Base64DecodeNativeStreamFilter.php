<?php

/*
 * This file is part of the Confetti package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Confetti\Test;

use Eloquent\Confetti\AbstractNativeStreamFilter;

class Base64DecodeNativeStreamFilter extends AbstractNativeStreamFilter
{
    protected function createTransform()
    {
        return new Base64DecodeTransform;
    }
}
