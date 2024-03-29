GzStreams for PHP
=================

Provides additional stream wrappers for guzzle and S3 streams so you can read
and write gzip files.

Installation with Composer
--------------------------

```shell
composer require cyberdummy/gzstream
```

Usage
-----

Usage in guzzle, wrap the body stream in the new decorator.
```php
use Cyberdummy\GzStream\GzStreamGuzzle;

$newBodyStream = new GzStreamGuzzle($psr7BodyStream);
```

Usage with S3 stream wrapper.
```php
use Cyberdummy\GzStream\GzS3Wrapper;

$s3Client = new Aws\S3\S3Client([
    'version'     => 'latest'
]);

# Register the wrapper as "s3gz"
GzS3Wrapper::register($s3Client, 's3gz');

# Stream a read
$stream = fopen('s3gz://somebucket/somegzippedfile.txt.gz', 'r');
$line = fgets($stream, 1024);

# Stream a write
$stream = fopen('s3gz://somebucket/somegzippedfile.txt.gz', 'w');
fwrite($stream, "Something to compress");
fclose($stream);
```
