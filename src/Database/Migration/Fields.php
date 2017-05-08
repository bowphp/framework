<?php
namespace Bow\Database\Migration;

use Bow\Support\Collection;
use Bow\Exception\ModelException;

class Fields
{
    /**
     * fields list
     *
     * @var Collection
     */
    private $fields;

    /**
     * define the primary key
     *
     * @var bool
     */
    private $primary = null;

    /**
     * last define field
     *
     * @var \StdClass
     */
    private $lastField = null;

    /**
     * Table name
     *
     * @var bool
     */
    private $table = null;

    /**
     * @var string
     */
    private $engine = 'MyISAM';

    /**
     * @var string
     */
    private $collate = 'utf8_unicode_ci';

    /**
     * @var string
     */
    private $character = 'UTF8';

    /**
     * define the auto increment field
     * @var \stdClass
     */
    private $autoincrement = false;

    /**
     * @var bool
     */
    private $displaySql = false;

    /**
     * @var array
     */
    private $dataBind = [];

    /**
     * Constructor
     *
     * @param string $table nom de la table
     */
    public function __construct($table)
    {
        $this->fields  = new Collection;
        $this->table = $table;
        return $this;
    }

    /**
     * charset, set the model default character name
     *
     * @param $character
     */
    public function charset($character)
    {
        $this->character = $character;
    }

    /**
     * setEngine, set the model engine name
     * @param $collate
     */
    public function collate($collate)
    {
        $this->collate = $collate;
    }

    /**
     * setEngine, set the model engine name
     * @param $engine
     */
    public function engine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * int
     *
     * @param string $field
     * @param int $size
     * @param bool $null
     * @param null|string $default
     *
     * @return Fields
     */
    public function integer($field, $size = 11, $null = false, $default = null)
    {
        return $this->loadWhole('int', $field, $size, $null, $default);
    }

    /**
     * tinyint
     *
     * @param string $field
     * @param bool $null
     * @param bool $size
     * @param null|string $default
     *
     * @return Fields
     */
    public function tinyInteger($field, $size = null, $null = false, $default = null)
    {
        return $this->loadWhole('tinyint', $field, $size, $null, $default);
    }

    /**
     * @param $field
     * @param null $default
     * @return Fields
     */
    public function boolean($field, $default = null)
    {
        return $this->tinyInteger($field, 1, false, $default);
    }

    /**
     * smallint
     *
     * @param string $field
     * @param bool $size
     * @param bool $null
     * @param null|string $default
     *
     * @return Fields
     * @throws \ErrorException
     */
    public function smallInteger($field, $size = null, $null = false, $default = null)
    {
        return $this->loadWhole('smallint', $field, $size, $null, $default);
    }

    /**
     * mediumint
     *
     * @param string $field
     * @param bool $size
     * @param bool $null
     * @param null|string $default
     *
     * @return Fields
     * @throws \ErrorException
     */
    public function mediumInteger($field, $size = null, $null = false, $default = null)
    {
        return $this->loadWhole('mediumint', $field, $size, $null, $default);
    }

    /**
     * bigint
     *
     * @param string $field
     * @param int $size
     * @param bool $null
     * @param null|string $default
     *
     * @return Fields
     */
    public function bigInteger($field, $size = 20, $null = false, $default = null)
    {
        return $this->loadWhole('bigint', $field, $size, $null, $default);
    }

    /**
     * bigint
     *
     * @param string $field
     * @param int $size
     * @param int $left
     * @param bool $null
     * @param null|string $default
     *
     * @return Fields
     */
    public function double($field, $size = 20, $left = 0, $null = false, $default = null)
    {
        if ($left > 0) {
            $size = '$size, $left';
        }
        return $this->loadWhole('double precision', $field, $size, $null, $default);
    }

    /**
     * bigint
     *
     * @param string $field
     * @param int $size
     * @param int $left
     * @param bool $null
     * @param null|string $default
     *
     * @return Fields
     */
    public function float($field, $size = 20, $left = 0, $null = false, $default = null)
    {
        if ($left > 0) {
            $size = '$size, $left';
        }
        return $this->loadWhole('float', $field, $size, $null, $default);
    }

    /**
     * varchar
     *
     * @param string $field
     * @param int $size
     * @param bool $null
     * @param null|string $default
     * @throws \Exception
     * @return Fields
     */
    public function string($field, $size = 255, $null = false, $default = null)
    {
        $type = 'varchar';
        if ($size > 255) {
            $type = 'text';
        }

        return $this->loadWhole($type, $field, $size, $null, $default);
    }

    /**
     * varchar
     *
     * @param string $field
     * @param bool $null
     * @param null|string $default
     * @throws \Exception
     * @return Fields
     */
    public function longText($field, $null = false, $default = null)
    {
        return $this->addField('mediumtext', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * varchar
     *
     * @param string $field
     * @param bool $null
     * @param null|string $default
     * @throws \Exception
     * @return Fields
     */
    public function mediumText($field, $null = false, $default = null)
    {
        return $this->addField('mediumtext', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * tinytext
     *
     * @param string $field
     * @param bool $null
     * @param null|string $default
     * @throws \Exception
     * @return Fields
     */
    public function tinyText($field, $null = false, $default = null)
    {
        return $this->addField('tinytext', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * text
     *
     * @param string $field
     * @param bool $null
     * @param null|string $default
     * @throws \Exception
     * @return Fields
     */
    public function text($field, $null = false, $default = null)
    {
        return $this->addField('text', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * binary
     *
     * @param string $field
     * @param int $size
     * @param bool $null
     * @param null|string $default
     * @throws \Exception
     * @return Fields
     */
    public function binary($field, $size = 8, $null = false, $default = null)
    {
        return $this->addField('binary', $field, [
            'null' => $null,
            'default' => $default,
            'size' => $size
        ]);
    }

    /**
     * blob
     *
     * @param string $field
     * @param bool $null
     * @param mixed $default
     * @throws \Exception
     * @return Fields
     */
    public function blob($field, $null = false, $default = null)
    {
        return $this->addField('blob', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * tiny blob
     *
     * @param string $field
     * @param bool $null
     * @param mixed $default
     * @throws \Exception
     * @return Fields
     */
    public function tinyBlob($field, $null = false, $default = null)
    {
        return $this->addField('tinyblob', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * long blob
     *
     * @param string $field
     * @param bool $null
     * @param mixed $default
     * @throws \Exception
     * @return Fields
     */
    public function longBlob($field, $null = false, $default = null)
    {
        return $this->addField('longblob', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * medium blob
     *
     * @param string $field
     * @param bool $null
     * @param mixed $default
     * @throws \Exception
     * @return Fields
     */
    public function mediumBlob($field, $null = false, $default = null)
    {
        return $this->addField('mediumblob', $field, [
            'null' => $null,
            'default' => $default
        ]);
    }

    /**
     * date
     *
     * @param string $field
     * @param bool $null
     *
     * @return Fields
     */
    public function date($field, $null = false)
    {
        $this->addField('date', $field, [
            'null' => $null
        ]);

        return $this;
    }

    /**
     * year
     *
     * @param string $field
     * @param bool $null
     *
     * @return Fields
     */
    public function year($field, $null = false)
    {
        $this->addField('year', $field, [
            'null' => $null
        ]);

        return $this;
    }

    /**
     * time
     *
     * @param string $field
     * @param bool $null
     *
     * @return Fields
     */
    public function time($field, $null = false)
    {
        $this->addField('time', $field, [
            'null' => $null
        ]);

        return $this;
    }

    /**
     * datetime
     *
     * @param string $field
     * @param string|bool $null
     *
     * @return Fields
     */
    public function dateTime($field, $null = false)
    {
        $this->addField('datetime', $field, [
            'null' => $null
        ]);

        return $this;
    }

    /**
     * timestamp
     *
     * @return Fields
     */
    public function timestamps()
    {
        $this->addField('timestamp', 'created_at', [
            'null' => true,
            'default' => 'CURRENT_TIMESTAMP'
        ]);

        $this->addField('timestamp', 'updated_at', [
            'null' => true,
            'default' => 'CURRENT_TIMESTAMP'
        ]);

        return $this;
    }

    /**
     * longint
     *
     * @param string $field
     * @param int $size
     * @param bool $null
     * @param null|string $default
     *
     * @return Fields
     */
    public function longInteger($field, $size = 20, $null = false, $default = null)
    {
        return $this->loadWhole('longint', $field, $size, $null, $default);
    }

    /**
     * @param string $field
     * @param int $size
     * @param bool|false $null
     * @param string $default
     * @return Fields
     * @throws ModelException
     */
    public function character($field, $size = 1, $null = false, $default = null)
    {
        if ($size > 4294967295) {
            throw new ModelException('Max size is 4294967295', E_USER_ERROR);
        }

        return $this->loadWhole('char', $field, $size, $null, $default);
    }

    /**
     * @param string $field
     * @param array $enums
     * @param bool $null
     * @param string $default
     * @return Fields
     */
    public function enumerate($field, array $enums, $null = false, $default = null)
    {
        return $this->addField('enum', $field, [
            'default' => $default,
            'null' => $null,
            'value' => $enums
        ]);
    }

    /**
     * autoincrement
     *
     * @param string $field
     * @throws ModelException
     * @return Fields
     */
    public function increment($field = null)
    {
        if ($field == null) {
            $field = 'id';
        }

        if ($this->autoincrement !== false) {
            return $this;
        }

        if ($this->lastField !== null) {
            if (! in_array($this->lastField->method, ['int', 'longint', 'bigint', 'mediumint', 'smallint', 'tinyint'])) {
                throw new ModelException('Cannot add autoincrement to ' . $this->lastField->method, 1);
            }

            $this->autoincrement = $this->lastField;
            $this->dataBind[$this->lastField->field]['auto'] = true;

            return $this;
        }

        if (! $field) {
            return $this;
        }

        $this->autoincrement = (object) [
            'method' => 'int',
            'field' => $field
        ];

        $this->integer($field)->primary();

        return $this;
    }

    /**
     * primary
     *
     * @param string|array $field
     * @throws ModelException
     * @return Fields
     */
    public function primary($field = null)
    {
        if ($this->primary !== null) {
            throw new ModelException('Primary key has already defined', E_ERROR);
        }

        if ($field === null) {
            $field = 'id';
            $this->addField('int', $field, [
                'null' => false,
                'auto' => true
            ]);
        }

        return $this->addIndexes('primary');
    }

    /**
     * indexe
     *
     * @return Fields
     */
    public function indexe()
    {
        return $this->addIndexes('indexe');
    }

    /**
     * unique
     *
     * @return Fields
     */
    public function unique()
    {
        return $this->addIndexes('unique');
    }

    /**
     * addIndexes crée un clause index sur le champs spécifié.
     *
     * @param string $indexType
     * @throws ModelException
     * @return Fields
     */
    private function addIndexes($indexType)
    {
        if ($this->lastField === null) {
            throw new ModelException('Cannot assign {$indexType}. Because field are not defined.', E_ERROR);
        }

        $last = $this->lastField;
        $this->fields->get($last->method)->update(
            $last->field, [$indexType => true]
        );

        return $this;
    }

    /**
     * addField
     *
     * @param string $method
     * @param string $field
     * @param string $data
     * @throws ModelException
     * @return Fields
     */
    private function addField($method, $field, $data)
    {
        $method = strtolower($method);

        if (! $this->fields->has($method)) {
            $this->fields->push(new Collection, $method);
        }

        if (! is_array($this->dataBind)) {
            $this->dataBind = [];
        }

        $bind = [
            'field' => $field,
            'type' => $method,
            'size' => isset($data['size']) ? $data['size'] : 0,
            'auto' => false
        ];

        if ($this->getAutoincrement() !== false) {
            if ($this->getAutoincrement()->field == $field) {
                $bind['auto'] = true;
            }
        }

        if ($method == 'enum') {
            $bind['default'] = $data['default'] != null ? $data['default'] : $data['value'][0];
        }

        $this->dataBind[$field] = $bind;

        if ($this->fields->get($method)->has($field)) {
            return $this;
        }

        // default index are at false
        $data['primary'] = false;
        $data['unique']  = false;
        $data['indexe']  = false;

        $this->fields->get($method)->push($data, $field);

        $this->lastField = (object) [
            'method' => $method,
            'field'  => $field
        ];

        return $this;
    }

    /**
     * loadWhole
     *
     * @param string      $method
     * @param string      $field
     * @param int         $size
     * @param bool        $null
     * @param null|string $default
     *
     * @return Fields
     */
    private function loadWhole($method, $field, $size = 20, $null = false, $default = null)
    {
        if (is_bool($size)) {
            $default = is_bool($null) ? null : $null;
            $null = $size;
            $size = 11;
        } else {
            if (is_string($size)) {
                $default = $size;
                $size    = 11;
                $null    = false;
            } else {
                if (is_string($null)) {
                    $default = $null;
                    $null    = false;
                }
            }
        }

        $this->addField($method, $field, [
            'size'    => $size,
            'null'    => $null,
            'default' => $default
        ]);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getDefineFields()
    {
        return $this->fields;
    }

    /**
     * @return bool
     */
    public function getDisplaySql()
    {
        return $this->displaySql;
    }

    /**
     * @return bool|string
     */
    public function getTableName()
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getCharacter()
    {
        return $this->character;
    }

    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return \stdClass
     */
    public function getAutoincrement()
    {
        return $this->autoincrement;
    }

    /**
     * @param bool $value
     */
    public function setAutoincrement($value)
    {
        $this->autoincrement = $value;
    }

    /**
     * @return array
     */
    public function getBindData()
    {
        return $this->dataBind;
    }

    /**
     * __call
     *
     * @param string $method
     * @param array $args
     * @throws \ErrorException
     */
    public function __call($method, $args)
    {
        throw new \ErrorException('Call to undefined method ' . static::class . '::'.$method.'()');
    }
}