<?php
namespace Bow\Database;
use Bow\Exception\TableException;

/**
 * Class SQLUnit
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Database
 */
class SqlUnity implements \IteratorAggregate
{
    /**
     * @var \StdClass
     */
    private $data;

    /**
     * @var string
     */
    private $id;

    /**
     * @var Table
     */
    private $table;

    /**
     * SqlUnity Contructor
     *
     * @param Table $table
     * @param mixed $id
     * @param null $data
     * @throws TableException
     */
    public function __construct(Table $table, $id, $data = null) {
        if ($data === null) {
            $data = $table->getOne();
        }

        if ($data == null) {
            throw new TableException('Aucune donnée trouvé.', E_ERROR);
        }

        $this->data = $data;
        $this->table = $table;
        $this->id = $id;
    }

    /**
     * Mise à jour d'un enregistrement
     *
     * @return mixed
     */
    public function save()
    {
        return $this->table->where('id', $this->id)->update($this->toArray());
    }

    /**
     * Suppression d'un enregistrement
     *
     * @return mixed
     */
    public function delete()
    {
        return $this->table->where('id', $this->id)->delete();
    }

    /**
     * convertir les informations de l'enregistrement en tableau
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this->data;
    }

    /**
     * Récuper une valeur dans l'enrégistrement
     *
     * @param $property
     * @return mixed|void
     */
    public function __get($property)
    {
        if (isset($this->data->$property)) {
            return $this->data->$property;
        }

        return null;
    }


    /**
     * Modifie une valeur dans l'enrégistrement
     *
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        if (isset($this->data->$property)) {
            $this->data->$property = $value;
        }
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}