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

use PHPUnit_Framework_TestCase;

class CompoundTransformTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->transformA = new \Rot13Transform;
        $this->transformB = new \Md5Transform;
        $this->transformC = new \Rot13Transform;
        $this->transforms = array(
            $this->transformA,
            $this->transformB,
            $this->transformC,
        );
        $this->transform = new CompoundTransform($this->transforms);
    }

    public function testTransforms()
    {
        $this->assertSame($this->transforms, $this->transform->transforms());
    }

    public function testTransform()
    {
        $input = 'foobar';
        $expected = array(
            str_rot13(md5(str_rot13($input))),
            strlen($input),
        );
        $context = null;
        $result = $this->transform->transform($input, $context, true);

        $this->assertSame($expected, $result);
    }

    public function testTransformWithEmptyString()
    {
        $input = '';
        $expected = array(
            str_rot13(md5(str_rot13($input))),
            strlen($input),
        );
        $context = null;
        $result = $this->transform->transform($input, $context, true);

        $this->assertSame($expected, $result);
    }

    public function testTransformByteByByte()
    {
        $input = 'foobar';
        $expected = str_rot13(md5(str_rot13($input)));
        $context = null;
        $result = '';

        foreach (str_split($input) as $index => $char) {
            list($output, $consumed) = $this->transform->transform(
                $char,
                $context,
                $index === strlen($input) - 1
            );

            $result .= $output;

            $this->assertSame(1, $consumed);
        }

        $this->assertSame($expected, $result);
    }
}
