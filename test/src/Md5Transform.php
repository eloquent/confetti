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

class Md5Transform implements TransformInterface
{
    public function transform($data, &$context, $isEnd = false)
    {
        if (null === $context) {
            $context = hash_init('md5');
        }

        hash_update($context, $data);

        if ($isEnd) {
            $output = hash_final($context);
        } else {
            $output = null;
        }

        return array($output, strlen($data), null);
    }
}
