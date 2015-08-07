<?php
namespace Cyberdummy\GzStream;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;

class GzStreamGuzzle implements StreamInterface
{
    use StreamDecoratorTrait;

    private $mode;
    private $headerLen = 0;
    private $footerLen = 0;
    private $hashCtx;
    private $writeSize = 0;
    private $filter;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;

        if (!$stream->isWritable()) {
            $this->mode = 'r';
        } else {
            $this->mode = 'w';
        }

        if ($this->mode == 'r') {
            $this->offsetHeader();
            // inflate stream filter
            $resource = StreamWrapper::getResource($stream);
            stream_filter_append($resource, 'zlib.inflate', STREAM_FILTER_READ);
            $this->stream = new Stream($resource);
        }
    }

    public function read($length)
    {
        $ret = $this->stream->read($length);
        return $ret;
    }

    public function tell()
    {
        if ($this->mode == 'w') {
            return $this->stream->tell();
        }

        return $this->stream->tell() - $this->headerLen;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence !== SEEK_SET || $offset < 0) {
            throw new \RuntimeException(sprintf(
                'Cannot seek to offset % with whence %s',
                $offset,
                $whence
            ));
        }

        if ($this->mode == 'r') {
            $offset += $this->headerLen;
        } else {
            // if we are seeeking the write has ended, put the footer on
            $this->writeFooter();
        }

        $this->stream->seek($offset);
    }

    private function offsetHeader()
    {
        $header = $this->stream->read(10);
        $this->headerLen += 10;
        $header = unpack("C10", $header);
        $flags  = $header[4];

        // FEXTRA
        if ($flags & 0x4) {
            $len = $this->stream->read(2);
            $len = unpack("S", $len);
            $this->stream->read($len[1]);
            $this->headerLen += 2+$len[1];
        }
        // FNAME
        if ($flags & 0x8) {
            $this->readToNull();
        }
        // FCOMMENT
        if ($flags & 0x10) {
            $this->readToNull();
        }
        // FHCRC
        if ($flags & 0x2) {
            $this->stream->read(2);
            $this->headerLen += 2;
        }
    }

    private function readToNull()
    {
        while (($chr = $this->stream->read(1)) !== false) {
            $this->headerLen++;
            if ($chr == "\0") {
                return;
            }
        }
    }

    private function writeHeader()
    {
        // no filename or mtime
        $header = "\x1F\x8B\x08\0".pack("V", 0)."\0\xFF";
        $this->stream->write($header);
        $this->headerLen = 10;
        $this->hashCtx = hash_init("crc32b");
    }

    public function write($string)
    {
        if (!$this->stream->isWritable()) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        if ($this->headerLen == 0) {
            $this->writeHeader();
            $resource = StreamWrapper::getResource($this->stream);
            $this->filter = stream_filter_append($resource, 'zlib.deflate', STREAM_FILTER_WRITE);
            $this->stream = new Stream($resource);
        }

        hash_update($this->hashCtx, $string);
        return $this->writeSize += $this->stream->write($string);
    }

    public function getSize ()
    {
        return $this->writeSize+$this->headerLen+$this->footerLen;
    }

    public function close()
    {
        if ($this->mode == 'w' && $this->headerLen > 0) {
            // write the close hash and len
            $this->writeFooter();
        }

        $this->stream->close();
    }

    private function writeFooter()
    {
        if ($this->footerLen > 0) {
            return;
        }

        $crc = hash_final($this->hashCtx, true);
        // remove filter
        if (is_resource($this->filter)) {
            stream_filter_remove($this->filter);
        }
        // need to reverse the hash_final string so it's little endian
        $this->stream->write($crc[3].$crc[2].$crc[1].$crc[0]);
        // write the original uncompressed file size
        $this->stream->write(pack("V", $this->writeSize));
        $this->footerLen = 8;
    }
}
