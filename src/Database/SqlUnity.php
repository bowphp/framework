<?php
namespace Bow\Database;

use Bow\Exception\TableException;
use \Carbon\Carbon;
/**
 * Class SQLUnit
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Database
 */
class SqlUnity implements \IteratorAggregate, \jsonSerializable
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
     * @param null|\stdClass $data
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
        return (array) $this->serialize();
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
        return new \ArrayIterator($this->serialize());
    }

    /**
     *
     */
    public function jsonSerialize()
    {
        return $this->serialize();
    }

    /**
     * @return array
     */
    private function serialize()
    {
        $data = $this->data;

        foreach($data as $key => $value) {
            if ($value instanceof Carbon) {
                $data->$key = (string) $value;
            }
        }

        return $data;
    }
}