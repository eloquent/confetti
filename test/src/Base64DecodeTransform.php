<?php

/*
 * This file is part of the Confetti package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

use Eloquent\Confetti\AbstractTransform;
use Eloquent\Confetti\BufferedTransformInterface;

class Base64DecodeTransform extends AbstractTransform implements
    BufferedTransformInterface
{
    public function transform($data, &$context, $isEnd = false)
    {
        $consume = $this->blocksSize(strlen($data), 4, $isEnd);
        if (!$consume) {
            return array('', 0, null);
        }

        $consumedData = substr($data, 0, $consume);
        if (1 === strlen(rtrim($consumedData, '=')) % 4) {
            return array('', 0, new Exception('Base64 decode failed.'));
        }

        $outputBuffer = base64_decode($consumedData, true);
        if (false === $outputBuffer) {
            return array('', 0, new Exception('Base64 decode failed.'));
        }

        return array($outputBuffer, $consume, null);
    }

    /**
     * Get the buffer size.
     *
     * This method is used to determine how much input is typically required
     * before output can be produced. This can provide performance benefits by
     * avoiding excessive method calls.
     *
     * @return integer The buffer size.
     */
    public function bufferSize()
    {
        return 4;
    }
}
