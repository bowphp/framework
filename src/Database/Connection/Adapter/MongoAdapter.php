<?php
namespace Bow\Database\Connection\Adapter;
use Bow\Database\Connection\AbstractConnection;

class MongoAdapter extends AbstractConnection
{
    /**
     * @var string
     */
    protected $name = 'mongo';

    /**
     * MongoAdapter constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function connection()
    {
        $this->config['hostname'];
        $dns = $this->config['hostname'].":".$this->config['port'];
        $client = new \MongoClient('mongodb://' . $dns, true, []);

        return $client;
    }
}