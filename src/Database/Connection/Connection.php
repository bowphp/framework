<?php

declare(strict_types=1);

namespace Bow\Database\Connection;

class Connection
{
    /**
     * The adaptor information
     *
     * @var AbstractConnection
     */
    private AbstractConnection $adapter;

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
     * Returns the connection to a database.
     *
     * @return AbstractConnection
     */
    public function getAdapter(): AbstractConnection
    {
        return $this->adapter;
    }

    /**
     * Set the adaptor
     *
     * @param AbstractConnection $adapter
     */
    public function setAdapter(AbstractConnection $adapter)
    {
        $this->adapter = $adapter;
    }
}
