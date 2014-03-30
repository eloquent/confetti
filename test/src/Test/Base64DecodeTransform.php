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

use Eloquent\Confetti\AbstractTransform;
use Exception;

class Base64DecodeTransform extends AbstractTransform
{
    public function transform($data, &$context, $isEnd = false)
    {
        $consume = $this->blocksSize(strlen($data), 4, $isEnd);
        if (!$consume) {
            return array('', 0);
        }

        $consumedData = substr($data, 0, $consume);
        if (1 === strlen(rtrim($consumedData, '=')) % 4) {
            throw new Exception('Base64 decode failed.');
        }

        $outputBuffer = base64_decode($consumedData, true);
        if (false === $outputBuffer) {
            throw new Exception('Base64 decode failed.');
        }

        return array($outputBuffer, $consume);
    }
}
