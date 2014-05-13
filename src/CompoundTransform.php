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

use SplObjectStorage;

/**
 * A transform that combines several other transforms in sequence.
 */
class CompoundTransform implements CompoundTransformInterface,
    BufferedTransformInterface
{
    /**
     * Construct a new compound transform.
     *
     * @param array<TransformInterface> $transforms The sequence of transforms to apply to incoming data.
     */
    public function __construct(array $transforms)
    {
        $this->innerTransform  = array_shift($transforms);
        $this->outerTransforms = $transforms;
    }

    /**
     * Get the sequence of transforms that are applied to incoming data.
     *
     * @return array<TransformInterface> The transforms.
     */
    public function transforms()
    {
        return array_merge(
            array($this->innerTransform),
            $this->outerTransforms
        );
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
        if ($this->innerTransform instanceof BufferedTransformInterface) {
            return $this->innerTransform->bufferSize();
        }

        return 1024;
    }

    /**
     * Transform the supplied data.
     *
     * This method may transform only part of the supplied data. The return
     * value includes information about how much data was actually consumed. The
     * transform can be forced to consume all data by passing a boolean true as
     * the $isEnd argument.
     *
     * The $context argument will initially be null, but any value assigned to
     * this variable will persist until the stream transformation is complete.
     * It can be used as a place to store state, such as a buffer.
     *
     * It is guaranteed that this method will be called with $isEnd = true once,
     * and only once, at the end of the stream transformation.
     *
     * @param string  $data     The data to transform.
     * @param mixed   &$context An arbitrary context value.
     * @param boolean $isEnd    True if all supplied data must be transformed.
     *
     * @return tuple<string,integer,mixed> A 3-tuple of the transformed data, the number of bytes consumed, and any resulting error.
     */
    public function transform($data, &$context, $isEnd = false)
    {
        if (null === $context) {
            $context = $this->createContexts();
        }

        list($data, $actualConsumed, $error) = $this->innerTransform->transform(
            $data,
            $context[$this->innerTransform]->context,
            $isEnd
        );

        foreach ($this->outerTransforms as $transform) {
            $currentContext = $context[$transform];
            $currentContext->buffer .= $data;

            if (null !== $error) {
                return array('', $actualConsumed, $error);
            }

            $bufferSize = strlen($currentContext->buffer);
            if (!$isEnd && $bufferSize < $currentContext->bufferSize) {
                return array('', $actualConsumed, null);
            }

            list($data, $consumed, $error) = $transform->transform(
                $currentContext->buffer,
                $currentContext->context,
                $isEnd
            );

            if ($bufferSize === $consumed) {
                $currentContext->buffer = '';
            } else {
                $currentContext->buffer = substr(
                    $currentContext->buffer,
                    $consumed
                );
            }
        }

        return array($data, $actualConsumed, $error);
    }

    private function createContexts()
    {
        $contexts = new SplObjectStorage;
        $context = new CompoundTransformSubContext;
        if ($this->innerTransform instanceof BufferedTransformInterface) {
            $context->bufferSize = $this->innerTransform->bufferSize();
        }
        $contexts[$this->innerTransform] = $context;

        foreach ($this->outerTransforms as $transform) {
            $context = new CompoundTransformSubContext;
            if ($transform instanceof BufferedTransformInterface) {
                $context->bufferSize = $transform->bufferSize();
            }

            $contexts[$transform] = $context;
        }

        return $contexts;
    }

    private $innerTransform;
    private $outerTransforms;
}
