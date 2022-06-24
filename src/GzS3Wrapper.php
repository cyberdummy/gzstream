<?php

namespace Cyberdummy\GzStream;

use Aws\S3\StreamWrapper;
use ReflectionClass;

class GzS3Wrapper extends StreamWrapper
{
    protected $altered = false;
    protected $bodyStream = false;

    protected function alter()
    {
        if ($this->altered !== false) {
            return;
        }

        $reflector = new ReflectionClass($this);
        $parent = $reflector->getParentClass();
        $name = $parent->getProperty('body');
        $name->setAccessible(true);
        $body = $name->getValue($this);
        $name->setValue($this, new GzStreamGuzzle($body));
        $this->bodyStream = $name->getValue($this);
        $this->altered = true;
    }

    public function stream_read($count)
    {
        $this->alter();
        return parent::stream_read($count);
    }

    public function stream_write($data)
    {
        $this->alter();
        return parent::stream_write($data);
    }
}
