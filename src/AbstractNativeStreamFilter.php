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

use Exception;
use php_user_filter;

/**
 * An abstract base class for implementing native stream filters based on
 * transforms.
 */
abstract class AbstractNativeStreamFilter extends php_user_filter
{
    /**
     * Called upon filter creation.
     */
    public function onCreate()
    {
        $this->transform = $this->createTransform();
        $this->buffer = '';
        $this->context = null;

        return true;
    }

    /**
     * Filter the input data through the transform.
     *
     * @param resource $input          The input bucket brigade.
     * @param resource $output         The output bucket brigade.
     * @param integer  &$consumedBytes The number of bytes consumed.
     * @param boolean  $isEnd          True if the stream is closing.
     *
     * @return integer The result code.
     */
    public function filter($input, $output, &$consumedBytes, $isEnd)
    {
        if ($isEnd) {
            $bucket = stream_bucket_new(STDIN, '');
        } else {
            $bucket = stream_bucket_make_writeable($input);
        }

        $hasOutput = false;
        while ($bucket) {
            $this->buffer .= $bucket->data;
            $bufferLength = strlen($this->buffer);

            try {
                list($outputBuffer, $thisConsumedBytes) = $this->transform
                    ->transform($this->buffer, $this->context, $isEnd);
            } catch (Exception $e) {
                return PSFS_ERR_FATAL;
            }

            $consumedBytes += $thisConsumedBytes;
            if ($bufferLength === $thisConsumedBytes) {
                $this->buffer = '';
            } else {
                $this->buffer = substr($this->buffer, $thisConsumedBytes);
            }

            if ('' !== $outputBuffer) {
                $bucket->data = $outputBuffer;
                stream_bucket_append($output, $bucket);
                $hasOutput = true;
            }

            $bucket = stream_bucket_make_writeable($input);
        }

        if ($hasOutput || $isEnd) {
            return PSFS_PASS_ON;
        }

        return PSFS_FEED_ME;
    }

    /**
     * Create the transform.
     *
     * @return TransformInterface The transform.
     */
    abstract protected function createTransform();

    private $transform;
    private $buffer;
    private $context;
}
