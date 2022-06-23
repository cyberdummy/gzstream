<?php

use Cyberdummy\GzStream\GzStreamGuzzle;
use GuzzleHttp\Psr7\Utils;

class GzStreamGuzzleTest extends \PHPUnit_Framework_TestCase
{
    public function testReadStream()
    {
        $content = gzencode('test');
        $a = Utils::streamFor($content);
        $b = new GzStreamGuzzle($a);
        $this->assertEquals('test', (string) $b);
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
