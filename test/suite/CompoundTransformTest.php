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

use Base64DecodeTransform;
use Md5Transform;
use Phake;
use PHPUnit_Framework_TestCase;
use Rot13Transform;

class CompoundTransformTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->transformA = new Rot13Transform;
        $this->transformB = new Md5Transform;
        $this->transformC = new Rot13Transform;
        $this->transforms = array($this->transformA, $this->transformB, $this->transformC);
        $this->transform = new CompoundTransform($this->transforms);
    }

    public function testTransforms()
    {
        $this->assertSame($this->transforms, $this->transform->transforms());
    }

    public function testBufferSizeDefault()
    {
        $this->assertSame(1024, $this->transform->bufferSize());
    }

    public function testBufferSizeWithBuffered()
    {
        $this->transform = new CompoundTransform(array(new Base64DecodeTransform));

        $this->assertSame(4, $this->transform->bufferSize());
    }

    public function testTransform()
    {
        $input = 'foobar';
        $expected = array(str_rot13(md5(str_rot13($input))), strlen($input), null);
        $result = $this->transform->transform($input, $context, true);

        $this->assertSame($expected, $result);
    }

    public function testTransformWithEmptyString()
    {
        $input = '';
        $expected = array(str_rot13(md5(str_rot13($input))), strlen($input), null);
        $result = $this->transform->transform($input, $context, true);

        $this->assertSame($expected, $result);
    }

    public function testTransformByteByByte()
    {
        $input = 'foobar';
        $inputLength = strlen($input);
        $expected = str_rot13(md5(str_rot13($input)));
        $result = '';

        foreach (str_split($input) as $index => $char) {
            list($output, $consumed) = $this->transform->transform($char, $context, $index === $inputLength - 1);
            $result .= $output;

            $this->assertSame(1, $consumed);
        }

        $this->assertSame($expected, $result);
    }

    public function testTransformErrorSingleTransform()
    {
        $this->transform = new CompoundTransform(array(new Base64DecodeTransform));
        list($output, $consumed, $error) = $this->transform->transform('!!!!', $context, true);

        $this->setExpectedException('Exception', 'Base64 decode failed.');
        throw $error;
    }

    public function testTransformErrorMultipleTransforms()
    {
        $this->transformA = Phake::partialMock('Base64DecodeTransform');
        $this->transformB = Phake::partialMock('Base64DecodeTransform');
        $this->transformC = Phake::partialMock('Base64DecodeTransform');
        $this->transforms = array($this->transformA, $this->transformB, $this->transformC);
        $this->transform = new CompoundTransform($this->transforms);
        list($output, $consumed, $error) = $this->transform->transform('ISEhIQ==', $context, true);

        Phake::inOrder(
            Phake::verify($this->transformA)->transform('ISEhIQ==', $this->anything(), true),
            Phake::verify($this->transformB)->transform('!!!!', $this->anything(), true)
        );
        Phake::verify($this->transformC, Phake::never())->transform(Phake::anyParameters());
        $this->setExpectedException('Exception', 'Base64 decode failed.');
        throw $error;
    }
}
