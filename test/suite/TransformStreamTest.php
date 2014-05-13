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
use Eloquent\Confetti\Test\TestWritableStream;
use Exception as NativeException;
use Md5Transform;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Eloquent\Confetti\TransformStream
 * @covers \Eloquent\Confetti\AbstractTransform
 */
class TransformStreamTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->transform = new Base64DecodeTransform;
        $this->stream = new TransformStream($this->transform, 6);

        $self = $this;

        $this->datasEmitted = $this->endsEmitted = $this->closesEmitted = $this->successesEmitted = 0;
        $this->output = '';
        $this->stream->on(
            'data',
            function ($data, $stream) use ($self) {
                $self->datasEmitted ++;
                $self->output .= $data;
            }
        );
        $this->stream->on(
            'end',
            function ($codec) use ($self) {
                $self->endsEmitted++;
            }
        );
        $this->stream->on(
            'close',
            function ($codec) use ($self) {
                $self->closesEmitted ++;
            }
        );
        $this->stream->on(
            'success',
            function ($codec) use ($self) {
                $self->successesEmitted ++;
            }
        );
        $this->stream->on(
            'error',
            function ($error, $codec) use ($self) {
                throw $error;
            }
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->transform, $this->stream->transform());
        $this->assertSame(6, $this->stream->bufferSize());
        $this->assertTrue($this->stream->isWritable());
        $this->assertTrue($this->stream->isReadable());
    }

    public function testConstructorDefaults()
    {
        $this->stream = new TransformStream(new Md5Transform);

        $this->assertSame(1024, $this->stream->bufferSize());
    }

    public function testConstructorDefaultsBuffered()
    {
        $this->stream = new TransformStream($this->transform);

        $this->assertSame(4, $this->stream->bufferSize());
    }

    public function testWriteEndEmpty()
    {
        $writeReturn = $this->stream->write('');
        $this->stream->end();

        $this->assertFalse($writeReturn);
        $this->assertSame('', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testWriteEndLessThanBuffer()
    {
        $writeReturn = $this->stream->write('Zm9v');
        $this->stream->end();

        $this->assertFalse($writeReturn);
        $this->assertSame('foo', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testWriteEndGreaterThanBuffer()
    {
        $writeReturn = $this->stream->write('Zm9vYmFy');
        $this->stream->end();

        $this->assertTrue($writeReturn);
        $this->assertSame('foobar', $this->output);
        $this->assertSame(2, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testWriteEndDoubleBufferSize()
    {
        $writeReturn = $this->stream->write('Zm9vYmFyYmF6');
        $this->stream->end();

        $this->assertTrue($writeReturn);
        $this->assertSame('foobarbaz', $this->output);
        $this->assertSame(2, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testEndEmpty()
    {
        $this->stream->end('');

        $this->assertSame('', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testEndLessThanBuffer()
    {
        $this->stream->end('Zm9v');

        $this->assertSame('foo', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testEndGreaterThanBuffer()
    {
        $this->stream->end('Zm9vYmFy');

        $this->assertSame('foobar', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testEndDoubleBufferSize()
    {
        $this->stream->end('Zm9vYmFyYmF6');

        $this->assertSame('foobarbaz', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
    }

    public function testClose()
    {
        $self = $this;
        $this->errorsEmitted = array();
        $this->stream->removeAllListeners('error');
        $this->stream->on(
            'error',
            function ($error, $codec) use ($self) {
                $self->errorsEmitted[] = $error;
            }
        );
        $this->stream->write('Zm9vYm');
        $this->stream->close();
        $this->stream->close();
        $this->stream->end('Fy');

        $this->assertFalse($this->stream->write('YmF6'));
        $this->assertSame('foo', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertSame(1, $this->successesEmitted);
        $this->assertEquals(array(new Exception\StreamClosedException), $this->errorsEmitted);
    }

    public function testPauseResume()
    {
        $this->stream->pause();

        $this->assertFalse($this->stream->write('Z'));
        $this->assertSame('', $this->output);

        $this->stream->resume();

        $this->assertTrue($this->stream->write('m9vYmFy'));
        $this->assertSame('foobar', $this->output);

        $this->stream->end('YmF6');

        $this->assertSame('foobarbaz', $this->output);
    }

    public function testPipe()
    {
        $destination = new TestWritableStream;
        $this->stream->pipe($destination);
        $this->stream->end('Zm9v');

        $this->assertSame('foo', $destination->data);
    }

    public function testTransformFailure()
    {
        $self = $this;
        $this->errorsEmitted = array();
        $this->stream->removeAllListeners('error');
        $this->stream->on(
            'error',
            function ($error, $codec) use ($self) {
                $self->errorsEmitted[] = $error;
            }
        );
        $errorA = new NativeException('Base64 decode failed.');
        $errorB = new Exception\StreamClosedException;
        $writeReturn = $this->stream->write('Zm9vYmFy');

        $this->assertTrue($writeReturn);
        $this->assertSame('foobar', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(0, $this->endsEmitted);
        $this->assertSame(0, $this->closesEmitted);
        $this->assertSame(array(), $this->errorsEmitted);

        $writeReturn = $this->stream->write('!!!!!!');

        $this->assertFalse($writeReturn);
        $this->assertSame('foobar', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertEquals(array($errorA), $this->errorsEmitted);

        $writeReturn = $this->stream->write('Zm9vYmFy');

        $this->assertFalse($writeReturn);
        $this->assertSame('foobar', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertEquals(array($errorA, $errorB), $this->errorsEmitted);

        $this->stream->end();

        $this->assertFalse($writeReturn);
        $this->assertSame('foobar', $this->output);
        $this->assertSame(1, $this->datasEmitted);
        $this->assertSame(1, $this->endsEmitted);
        $this->assertSame(1, $this->closesEmitted);
        $this->assertEquals(array($errorA, $errorB), $this->errorsEmitted);
    }
}
