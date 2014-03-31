<?php

/*
 * This file is part of the Confetti package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

use Eloquent\Confetti\TransformStream;

class DocumentationTest extends PHPUnit_Framework_TestCase
{
    public function testTransformStreamUsage()
    {
        $this->expectOutputString('foobar');

$stream = new TransformStream(new Base64DecodeTransform);
$stream->on(
    'data',
    function ($data, $stream) {
        echo $data;
    }
);
$stream->on(
    'error',
    function ($error, $stream) {
        throw $error;
    }
);

try {
    $stream->write('Zm9v'); // outputs 'foo'
    $stream->end('YmFy');   // outputs 'bar'
} catch (Exception $e) {
    // unable to decode
}
    }

    // =========================================================================

    public function testTransformStringUsage()
    {
        $this->expectOutputString('sbbone');

$transform = new Rot13Transform;

list($data) = $transform->transform('foobar', $context, true);
echo $data; // outputs 'sbbone'
    }

    // =========================================================================

    public function testTransformReactStreamUsage()
    {
        $this->expectOutputString('sbbone');

$stream = new TransformStream(new Rot13Transform);
$stream->on(
    'data',
    function ($data, $stream) {
        echo $data;
    }
);

$stream->end('foobar'); // outputs 'sbbone'
    }

    // =========================================================================

    public function testTransformNativeFilterUsage()
    {
        $path = sprintf('%s/%s', sys_get_temp_dir(), uniqid('confetti-'));
        $this->expectOutputString('sbbone');

stream_filter_register('confetti.rot13', 'Rot13NativeStreamFilter');

// $path = '/path/to/file';
$stream = fopen($path, 'wb');
stream_filter_append($stream, 'confetti.rot13');
fwrite($stream, 'foobar');
fclose($stream);
echo file_get_contents($path); // outputs 'sbbone'

        unlink($path);
    }

    // =========================================================================

    public function testMd5TransformUsage()
    {
        $this->expectOutputString('3858f62230ac3c915f300c664312c63f');

$stream = new TransformStream(new Md5Transform);
$stream->on(
    'data',
    function ($data, $stream) {
        echo $data;
    }
);

$stream->write('foo');
$stream->end('bar'); // outputs '3858f62230ac3c915f300c664312c63f'
    }
}
