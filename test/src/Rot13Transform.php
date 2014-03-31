<?php

/*
 * This file is part of the Confetti package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

use Eloquent\Confetti\TransformInterface;

class Rot13Transform implements TransformInterface
{
    public function transform($data, &$context, $isEnd = false)
    {
        return array(str_rot13($data), strlen($data));
    }
}
