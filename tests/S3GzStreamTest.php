<?php

use Cyberdummy\GzStream\GzS3Wrapper;
use PHPUnit\Framework\TestCase;

class S3GzStreamTest extends TestCase
{
    private $s3Client;

    public function setUp(): void
    {
        $dotenv = new Dotenv\Dotenv(__DIR__);
        $dotenv->load();

        $this->s3Client = new Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => getenv('AWS_REGION'),
        ]);

        GzS3Wrapper::register($this->s3Client, 's3gz');
    }

    public function testReadStream()
    {
        $result = $this->s3Client->putObject(array(
            'Bucket' => getenv('AWS_S3_BUCKET'),
            'Key'    => 's3ReadTest.txt.gz',
            'SourceFile' => __DIR__.'/s3ReadTest.txt.gz',
        ));

        $stream = fopen('s3gz://'.getenv('AWS_S3_BUCKET').'/s3ReadTest.txt.gz', 'r');
        $i = 1;
        while (($buffer = fgets($stream, 4096)) !== false) {
            $this->assertSame("Line {$i}", trim($buffer));
            $i++;
        }
        fclose($stream);
    }

    public function testWriteStream()
    {
        $str = "This is a string I want to compress.";
        $stream = fopen('s3gz://'.getenv('AWS_S3_BUCKET').'/s3WriteTest.txt.gz', 'w');
        fwrite($stream, $str);
        fclose($stream);

        $stream = fopen('s3gz://'.getenv('AWS_S3_BUCKET').'/s3WriteTest.txt.gz', 'r');
        while (($buffer = fgets($stream, 4096)) !== false) {
            $this->assertSame($str, trim($buffer));
        }
        fclose($stream);
    }
}
