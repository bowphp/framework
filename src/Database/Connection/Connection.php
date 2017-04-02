<?php
namespace Bow\Database\Connection;

class Connection
{
    /**
     * @var AbstractConnection
     */
    private $adapter;

    /**
     * Connection constructor.
     *
     * @param AbstractConnection $adapter
     */
    public function __construct(AbstractConnection $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Permet de retourner la connection a une base de donnée.
     *
     * @return AbstractConnection
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Permet de modifier l'adapter
     *
     * @param AbstractConnection $adapter
     */
    public function setAdapter(AbstractConnection $adapter)
    {
        $this->adapter = $adapter;
    }
}