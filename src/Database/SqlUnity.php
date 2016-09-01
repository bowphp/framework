<?php
namespace Bow\Database;

use \Carbon\Carbon;
use Bow\Exception\TableException;
/**
 * Class SQLUnit
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Database
 */
class SqlUnity implements \IteratorAggregate, \JsonSerializable
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
     * @var string
     */
    private $foreign;

    /**
     * @var string
     */
    private $mergeTableName;

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
        $data = $this->data;
        if ($this->mergeTableName !== null) {
            unset($data->${$this->mergeTableName});
        }
        return $this->table->where('id', $this->id)->update($this->serialize($data));
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
     * @param string $id
     */
    public function foreign($id)
    {
        $this->foreign = $id;
    }

    /**
     * Join avec une autre table
     *
     * @param string $table
     * @return self
     */
    public function merge($table)
    {
        $foreign = 'id';
        if ($this->foreign !== null) {
            $foreign = $this->foreign;
        }
        $this->{$table} = Database::table($table)->where($foreign, $this->id)->get();
        $this->mergeTableName = $table;
        return $this;
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
     * Quand un foreach est lancé sur l'instance de SqlUnit
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * Appel de la metod json_encode sur l'instance de SqlUnit.
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param array $data
     * @return array
     */
    private function serialize($data = [])
    {
        if (empty($data)) {
            $data = $this->data;
        }

        foreach($data as $key => $value) {
            if ($value instanceof Carbon) {
                $data->$key = (string) $value;
            }
        }

        return $data;
    }
}