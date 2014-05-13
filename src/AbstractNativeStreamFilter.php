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

        if ($this->transform instanceof BufferedTransformInterface) {
            $this->bufferSize = $this->transform->bufferSize();
        } else {
            $this->bufferSize = 1024;
        }

        return true;
    }

    /**
     * Filter the input data through the transform.
     *
     * @param resource $input     The input bucket brigade.
     * @param resource $output    The output bucket brigade.
     * @param integer  &$consumed The number of bytes consumed.
     * @param boolean  $isEnd     True if the stream is closing.
     *
     * @return integer The result code.
     */
    public function filter($input, $output, &$consumed, $isEnd)
    {
        if ($isEnd) {
            $bucket = stream_bucket_new(STDIN, '');
        } else {
            $bucket = stream_bucket_make_writeable($input);
        }

        $hasOutput = false;
        while ($bucket) {
            $this->buffer .= $bucket->data;
            $bufferSize = strlen($this->buffer);

            if (!$isEnd && $bufferSize < $this->bufferSize) {
                $bucket = stream_bucket_make_writeable($input);

                continue;
            }

            list($outputBuffer, $thisConsumed, $error) = $this->transform
                ->transform($this->buffer, $this->context, $isEnd);

            $consumed += $thisConsumed;
            if ($bufferSize === $thisConsumed) {
                $this->buffer = '';
            } else {
                $this->buffer = substr($this->buffer, $thisConsumed);
            }

            if ('' !== $outputBuffer) {
                $bucket->data = $outputBuffer;
                stream_bucket_append($output, $bucket);
                $hasOutput = true;
            }

            if (null !== $error) {
                return PSFS_ERR_FATAL;
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
    private $bufferSize;
    private $context;
}
