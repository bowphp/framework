<?php
namespace Bow\Resource\AWS;

use Aws\Sdk;
use Aws\S3\S3Client;

class AwsS3Client
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
        return $this->s3->getObjectAcl(
            [
            $url
            ]
        )->get('acl');
    }

    /**
     * @param string $url
     * @param string $data
     * @param string $visibility
     * @return mixed
     */
    public function put($url, $data, $visibility = 'private')
    {
        return $this->s3->upload(
            $this->config['bucket'],
            $url,
            $data,
            $visibility
        );
    }

    /**
     * @param $url
     * @return \Aws\Result
     */
    public function delete($url)
    {
        if (!is_array($url)) {
            $url = [$url];
        }

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
}
