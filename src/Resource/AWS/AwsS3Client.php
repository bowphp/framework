<?php
namespace Bow\Resource\AWS;

use Aws\Sdk;
use Aws\S3\S3Client;
use Bow\Resource\FilesystemInterface;

class AwsS3Client implements FilesystemInterface
{
    /**
     * @var S3Client
     */
    private $s3;

    /**
     * @var string
     */
    private $version;

    /**
     * @var array
     */
    private $config;

    /**
     * AS3Client constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->s3 = new S3Client($config);

        $this->version = Sdk::VERSION;
    }

    /**
     * @param string $url
     */
    public function get($url)
    {
        $this->s3->getObjectUrl(
            $this->config['bucket'],
            $url
        );
    }

    /**
     * @param string $url
     * @param string $to
     * @return mixed
     */
    public function copy($url, $to)
    {
        return $this->s3->copy(
            $this->config['bucket'],
            $url,
            $this->config['bucket'],
            $to
        );
    }

    /**
     * @param string $url
     * @return \Aws\Result
     */
    public function deleteDirectory($url)
    {
        return $this->s3->deleteObjects([$url]);
    }

    /**
     * @param $url
     * @return \Aws\Result
     */
    public function visibility($url)
    {
        return $this->s3->getObjectAcl([$url])->get('acl');
    }

    /**
     * @param string $url
     * @param string $data
     * @param string $visibility
     * @return mixed
     */
    public function put($url, $data, $visibility = 'private')
    {
        return $this->s3->upload($this->config['bucket'], $url, $data, $visibility);
    }

    /**
     * @param $url
     * @return \Aws\Result
     */
    public function delete($url)
    {
        $url = (array) $url;

        return $this->s3->deleteObject($url);
    }

    /**
     * @param string $url
     * @param string $new
     * @return mixed
     */
    public function rename($url, $new)
    {
        return $this->copy($url, $new);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->s3->doesObjectExist(
            $this->config['bucket'],
            $key
        );
    }

    /**
     * @return S3Client
     */
    public function getS3Instance()
    {
        return $this->s3;
    }

    /**
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @inheritDoc
     */
    public function store($file, $location, $size, array $extension, callable $cb)
    {
        $this->s3;
    }

    /**
     * @inheritDoc
     */
    public function append($file, $content)
    {
        // TODO: Implement append() method.
    }

    /**
     * @inheritDoc
     */
    public function prepend($file, $content)
    {
        // TODO: Implement prepend() method.
    }

    /**
     * @inheritDoc
     */
    public function files($dirname)
    {
        $this->s3->listObjects([]);
    }

    /**
     * @inheritDoc
     */
    public function directories($dirname)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false)
    {
        // TODO: Implement makeDirectory() method.
    }

    /**
     * @inheritDoc
     */
    public function move($targer_file, $source_file)
    {
        // TODO: Implement move() method.
    }

    /**
     * @inheritDoc
     */
    public function extension($filename)
    {
        // TODO: Implement extension() method.
    }

    /**
     * @inheritDoc
     */
    public function isFile($filename)
    {
        $this->s3->getObject($filename);
    }

    /**
     * @inheritDoc
     */
    public function isDirectory($dirname)
    {
        // TODO: Implement isDirectory() method.
    }

    /**
     * @inheritDoc
     */
    public function resolvePath($filename)
    {
        // TODO: Implement resolvePath() method.
    }
}
