<?php

use Cyberdummy\GzStream\GzStreamGuzzle;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\Stream;

class GzStreamGuzzleTest extends \PHPUnit_Framework_TestCase
{
    public function testReadStream()
    {
        $file = __DIR__.'/readTest.gz';
        $fh = fopen($file, 'r');
        $b = new GzStreamGuzzle(new Stream($fh));
        $this->assertEquals('The quick brown fox jumps over the lazy dog', (string) $b);
    }

    public function testWriteStream()
    {
        $dest = __DIR__.'/writeTest.gz';
        $fh = fopen($dest, 'w');
        $a = Utils::streamFor($fh);
        $gzStream = new GzStreamGuzzle($a);
        $content = 'The quick brown fox jumps over the lazy dog';
        $gzStream->write($content);
        $gzStream->close();

        // test with zlib
        $fh = gzopen($dest, 'r');
        $buffer = gzread($fh, 4096);
        $this->assertEquals($content, $buffer);
        gzclose($fh);
    }

    public function testClose()
    {
        $dest = __DIR__.'/closeTest.gz';
        $fh = fopen($dest, 'w');
        $a = Utils::streamFor($fh);
        $gzStream = new GzStreamGuzzle($a);
        $content = 'The quick brown fox jumps over the lazy dog';
        $gzStream->write($content);
    }
}
