<?php
use GuzzleHttp\Psr7;
use Cyberdummy\GzStream\GzStreamGuzzle;

class GzStreamGuzzleTest extends \PHPUnit_Framework_TestCase
{
    public function testReadStream()
    {
        $content = gzencode('test');
        $a = Psr7\stream_for($content);
        $b = new GzStreamGuzzle($a);
        $this->assertEquals('test', (string) $b);
    }

    public function testWriteStream()
    {
        $dest = __DIR__.'/writeTest.gz';
        $fh = fopen($dest, 'w');
        $a = Psr7\stream_for($fh);
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
        $a = Psr7\stream_for($fh);
        $gzStream = new GzStreamGuzzle($a);
        $content = 'The quick brown fox jumps over the lazy dog';
        $gzStream->write($content);
    }
}
