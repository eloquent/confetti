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

class AbstractNativeStreamFilterTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        stream_filter_register('confetti.test', 'Base64DecodeNativeStreamFilter');
    }

    public function testFilter()
    {
        $path = tempnam(sys_get_temp_dir(), 'confetti');
        $stream = fopen($path, 'wb');
        stream_filter_append($stream, 'confetti.test');
        fwrite($stream, 'Z');
        fwrite($stream, 'm9vYmFy');
        fclose($stream);
        $actual = file_get_contents($path);
        unlink($path);

        $this->assertSame('foobar', $actual);
    }

    public function testFilterFailure()
    {
        $path = tempnam(sys_get_temp_dir(), 'confetti');
        $stream = fopen($path, 'wb');
        stream_filter_append($stream, 'confetti.test');
        $actual = fwrite($stream, '$');
        fclose($stream);
        unlink($path);

        $this->assertSame(0, $actual);
    }
}
