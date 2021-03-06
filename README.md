# Confetti

*Streaming data transformation system for PHP.*

[![The most recent stable version is 0.3.1][version-image]][Semantic versioning]
[![Current build status image][build-image]][Current build status]
[![Current coverage status image][coverage-image]][Current coverage status]

## Installation and documentation

* Available as [Composer] package [eloquent/confetti].
* [API documentation] available.

## What is *Confetti*?

*Confetti* is a system for implementing streaming data transformation. It allows
a single transform implementation to be used for strings, [React streams], and
[native PHP stream filters]. *Confetti* transforms are simple to implement, and
can facilitate a wide range of stream manipulations, such as encoding,
encryption, and incremental hashing.

This library contains no transform implementations. For real-world examples of
data transform usage, see [Endec], and [Lockbox].

## Transform streams

The [TransformStream] class provides a simple way to create a [React stream]
wrapper around a transform. It implements both [ReadableStreamInterface] and
[WritableStreamInterface]. Its usage is as follows:

```php
use Eloquent\Confetti\TransformStream;

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
```

### The `success` event

In addition to the events used by [React streams] \(`data`, `end`, `close`,
`error`), The [TransformStream] class will emit a `success` event upon closing
if there have been no errors. The `success` callback will be passed the stream,
and can access the inner transform by calling `$stream->transform()`:

```php
$stream->on(
    'success',
    function ($stream) {
        echo get_class($stream->transform());
    }
);
```

### Combining transforms

Any number of transforms can be combined into a single transform instance by
using the [CompoundTransform] class. This is useful for creating streams that
apply multiple transforms in sequence:

```php
use Eloquent\Confetti\CompoundTransform;
use Eloquent\Confetti\TransformStream;

$stream = new TransformStream(
    new CompoundTransform(array(new Rot13Transform, new Base64DecodeTransform))
);
$stream->on(
    'data',
    function ($data, $stream) {
        echo $data;
    }
);

$stream->write('Mz9i'); // outputs 'foo'
$stream->end('LzSl');   // outputs 'bar'
```

## Implementing a transform

At the heart of *Confetti* lies the [TransformInterface] interface. A correctly
implemented transform can be used for both string-based, and streaming
transformations.

A simple transform might look like the following:

```php
use Eloquent\Confetti\TransformInterface;

class Rot13Transform implements TransformInterface
{
    public function transform($data, &$context, $isEnd = false)
    {
        return array(str_rot13($data), strlen($data), null);
    }
}
```

The transform receives an arbitrary amount of data as a string, and returns an
tuple (array) where the first element is the transformed data, the second
element is the amount of data consumed in bytes (in this example, the data is
always completely consumed), and the third element is any error that occurred.

This transform can now be utilized in several ways. To apply the transform to a
string, simply call `transform()` with a boolean true for the `$isEnd` argument:

```php
$transform = new Rot13Transform;

list($data) = $transform->transform('foobar', $context, true);
echo $data; // outputs 'sbbone'
```

To use the transform as a [React stream], create a new [TransformStream] and
inject the transform.

```php
use Eloquent\Confetti\TransformStream;

$stream = new TransformStream(new Rot13Transform);
$stream->on(
    'data',
    function ($data, $stream) {
        echo $data;
    }
);

$stream->end('foobar'); // outputs 'sbbone'
```

## Native stream filters

Transforms can also be used to implement [native PHP stream filters], but PHP's
stream filter system requires that each filter is implemented as an individual
class. *Confetti* includes an abstract class that greatly simplifies
implementing stream filters.

To create a stream filter simply extend from `AbstractNativeStreamFilter`, and
implement the `createTransform()` method:

```php
use Eloquent\Confetti\AbstractNativeStreamFilter;

class Rot13NativeStreamFilter extends AbstractNativeStreamFilter
{
    protected function createTransform()
    {
        return new Rot13Transform;
    }
}
```

Once the filter is registered, it can be used like any other stream filter:

```php
stream_filter_register('confetti.rot13', 'Rot13NativeStreamFilter');

$path = '/path/to/file';
$stream = fopen($path, 'wb');
stream_filter_append($stream, 'confetti.rot13');
fwrite($stream, 'foobar');
fclose($stream);
echo file_get_contents($path); // outputs 'sbbone'
```

Note that the only way to detect a native stream filter failure is to check the
length of data written. If the length is 0, it indicates an error:

```php
stream_filter_register(
    'confetti.base64decode',
    'Base64DecodeNativeStreamFilter'
);

$path = '/path/to/file';
$stream = fopen($path, 'wb');
stream_filter_append($stream, 'confetti.base64decode');
if (!fwrite($stream, '!!!!')) {
    echo 'Decoding failed.';
}
fclose($stream);
```

## Complex transforms

More complex transforms may not be able to consume data byte-by-byte. As an
example, attempting to base64 decode each byte as it is received would result in
invalid output. The correct data cannot be known until a full block of 4 base64
bytes is received. There's also the possibility of receiving bytes that are
invalid for the base64 encoding scheme.

A base64 decode transform might be implemented like so:

```php
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

    public function bufferSize()
    {
        return 4;
    }
}
```

This transform will now decode blocks of base64 data and append the result to
the output buffer. The `bufferSize()` method suggests an appropriate buffer size
for classes that consume this transform (in this case, 4 bytes - the size of a
base64 block), and the call to `AbstractTransform::blocksSize()` ensures that
data is only consumed in blocks of 4 bytes at a time. If an invalid byte is
passed, or the data stream ends at an invalid number of bytes, an exception is
returned as the third tuple element to indicate the error.

## The context parameter

Transforms also have the ability to utilize the `$context` parameter. This
parameter can be assigned any value, and is used as an arbitrary data store that
is guaranteed to persist across the lifetime of the stream transformation. When
the first call to `transform()` is made, the `$context` argument will be `null`.
On subsequent calls, `$context` will contain whatever was previously assigned to
the variable. This allows for advanced behavior, such as buffering.

As an example of the context's usage, consider a transform that produces an MD5
hash of the incoming data:

```php
use Eloquent\Confetti\TransformInterface;

class Md5Transform implements TransformInterface
{
    public function transform($data, &$context, $isEnd = false)
    {
        if (null === $context) {
            $context = hash_init('md5');
        }

        hash_update($context, $data);

        if ($isEnd) {
            $output = hash_final($context);
        } else {
            $output = '';
        }

        return array($output, strlen($data), null);
    }
}
```

In this case, the `$context` parameter is used to store the hash context. The
transform now functions as follows:

```php
use Eloquent\Confetti\TransformStream;

$stream = new TransformStream(new Md5Transform);
$stream->on(
    'data',
    function ($data, $stream) {
        echo $data;
    }
);

$stream->write('foo');
$stream->end('bar'); // outputs '3858f62230ac3c915f300c664312c63f'
```

<!-- References -->

[CompoundTransform]: http://lqnt.co/confetti/artifacts/documentation/api/Eloquent/Confetti/CompoundTransform.html
[Endec]: https://github.com/eloquent/endec
[Lockbox]: https://github.com/eloquent/lockbox-php
[native PHP stream filters]: http://php.net/stream.filters
[React stream]: https://github.com/reactphp/react/tree/0.4/src/Stream
[React streams]: https://github.com/reactphp/react/tree/0.4/src/Stream
[ReadableStreamInterface]: https://github.com/reactphp/react/blob/0.4/src/Stream/ReadableStreamInterface.php
[TransformInterface]: http://lqnt.co/confetti/artifacts/documentation/api/Eloquent/Confetti/TransformInterface.html
[TransformStream]: http://lqnt.co/confetti/artifacts/documentation/api/Eloquent/Confetti/TransformStream.html
[WritableStreamInterface]: https://github.com/reactphp/react/blob/0.4/src/Stream/WritableStreamInterface.php

[API documentation]: http://lqnt.co/confetti/artifacts/documentation/api/
[Composer]: http://getcomposer.org/
[build-image]: http://img.shields.io/travis/eloquent/confetti/develop.svg "Current build status for the develop branch"
[Current build status]: https://travis-ci.org/eloquent/confetti
[coverage-image]: http://img.shields.io/coveralls/eloquent/confetti/develop.svg "Current test coverage for the develop branch"
[Current coverage status]: https://coveralls.io/r/eloquent/confetti
[eloquent/confetti]: https://packagist.org/packages/eloquent/confetti
[Semantic versioning]: http://semver.org/
[version-image]: http://img.shields.io/:semver-0.3.1-yellow.svg "This project uses semantic versioning"
