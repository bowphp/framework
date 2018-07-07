<?php
namespace Bow\Database\Migration;

use Bow\Support\Collection;
use Bow\Exception\ModelException;

class TablePrinter
{
    /**
     * fields list
     *
     * @var Collection
     */
    private $fields;

    /**
     * @var array
     */
    private $rangs = [];

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
    private $engine = 'InnoDB';

    /**
     * @var string
     */
    private $collate = 'utf8mb4_unicode_ci';

    /**
     * @var string
     */
    private $charset = 'utf8mb4';

    /**
     * define the auto increment field
     *
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
     * TablePrinter constructor.
     *
     * @param string $table
     * @param string $charset
     * @param string $collate
     */
    public function __construct($table, $charset = null, $collate = null)
    {
        $this->fields = new Collection;

        $this->table = $table;

        if (!is_null($charset)) {
            $this->charset = $charset;
        }

        if (!is_null($collate)) {
            $this->collate = $collate;
        }

        return $this;
    }

    /**
     * @param $size
     * @return TablePrinter
     */
    public function size($size)
    {
        $this->rangs[$this->lastField->field]['data']['size'] = $size;

        return $this;
    }

    /**
     * nullable
     *
     * @return TablePrinter
     */
    public function nullable()
    {
        $this->rangs[$this->lastField->field]['data']['null'] = true;

        return $this;
    }

    /**
     * @param $value
     * @return TablePrinter
     */
    public function default($value)
    {
        $this->rangs[$this->lastField->field]['data']['default'] = $value;

        return $this;
    }

    /**
     * @return TablePrinter
     */
    public function unsigned()
    {
        $this->rangs[$this->lastField->field]['data']['unsigned'] = true;

        return $this;
    }

    /**
     * charset, set the model default character name
     *
     * @param string $charset
     * @return TablePrinter
     */
    public function charset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * setEngine, set the model engine name
     *
     * @param string $collate
     * @return TablePrinter
     */
    public function collate($collate)
    {
        $this->collate = $collate;

        return $this;
    }

    /**
     * setEngine, set the model engine name
     *
     * @param string $engine
     * @return TablePrinter
     */
    public function engine($engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * int
     *
     * @param string $field
     * @param int    $size
     *
     * @return TablePrinter
     */
    public function integer($field, $size = 11)
    {
        return $this->loadWhole('int', $field, $size);
    }

    /**
     * tinyint
     *
     * @param string  $field
     * @param integer $size
     *
     * @return TablePrinter
     */
    public function tinyInteger($field, $size = 1)
    {
        return $this->loadWhole('tinyint', $field, $size);
    }

    /**
     * @param $field
     * @return TablePrinter
     */
    public function boolean($field)
    {
        return $this->tinyInteger($field, 1);
    }

    /**
     * smallint
     *
     * @param string $field
     * @param bool   $size
     * @return TablePrinter
     */
    public function smallInteger($field, $size = null)
    {
        return $this->loadWhole('smallint', $field, $size);
    }

    /**
     * mediumint
     *
     * @param string $field
     * @return TablePrinter
     */
    public function mediumInteger($field)
    {
        return $this->loadWhole('mediumint', $field, null);
    }

    /**
     * bigint
     *
     * @param string $field
     * @param int    $size
     * @return TablePrinter
     */
    public function bigInteger($field, $size = 20)
    {
        return $this->loadWhole('bigint', $field, $size);
    }

    /**
     * bigint
     *
     * @param string $field
     * @param int    $size
     * @param int    $left
     * @return TablePrinter
     */
    public function double($field, $size = 20, $left = 0)
    {
        if ($left > 0) {
            $size = '$size, $left';
        }

        return $this->loadWhole('double precision', $field, $size);
    }

    /**
     * bigint
     *
     * @param string $field
     * @param int    $size
     * @param int    $left
     *
     * @return TablePrinter
     */
    public function float($field, $size = 20, $left = 0)
    {
        if ($left > 0) {
            $size = '$size, $left';
        }

        return $this->loadWhole('float', $field, $size);
    }

    /**
     * varchar
     *
     * @param  string $field
     * @param  int    $size
     * @throws \Exception
     * @return TablePrinter
     */
    public function string($field, $size = 255)
    {
        $type = 'varchar';
        
        if ($size > 255) {
            $type = 'text';
        }

        return $this->loadWhole($type, $field, $size);
    }

    /**
     * varchar
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function longText($field)
    {
        return $this->addField('mediumtext', $field, [ 'null' => false ]);
    }

    /**
     * varchar
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function mediumText($field)
    {
        return $this->addField('mediumtext', $field, ['null' => false]);
    }

    /**
     * tinytext
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function tinyText($field)
    {
        return $this->addField('tinytext', $field, ['null' => false]);
    }

    /**
     * text
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function text($field)
    {
        return $this->addField('text', $field, ['null' => false]);
    }

    /**
     * binary
     *
     * @param  string $field
     * @param  int    $size
     * @throws \Exception
     * @return TablePrinter
     */
    public function binary($field, $size = 8)
    {
        return $this->addField('binary', $field, [
            'null' => false,
            'size' => $size
        ]);
    }

    /**
     * blob
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function blob($field)
    {
        return $this->addField('blob', $field, ['null' => false]);
    }

    /**
     * tiny blob
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function tinyBlob($field)
    {
        return $this->addField('tinyblob', $field, ['null' => false]);
    }

    /**
     * long blob
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function longBlob($field)
    {
        return $this->addField('longblob', $field, ['null' => false]);
    }

    /**
     * medium blob
     *
     * @param  string $field
     * @throws \Exception
     * @return TablePrinter
     */
    public function mediumBlob($field)
    {
        return $this->addField('mediumblob', $field, ['null' => false]);
    }

    /**
     * date
     *
     * @param string $field
     * @return TablePrinter
     * @throws
     */
    public function date($field)
    {
        $this->addField('date', $field, ['null' => false]);

        return $this;
    }

    /**
     * year
     *
     * @param string $field
     * @return TablePrinter
     * @throws
     */
    public function year($field)
    {
        $this->addField(
            'year',
            $field,
            [
            'null' => false
            ]
        );

        return $this;
    }

    /**
     * time
     *
     * @param string $field
     * @return TablePrinter
     * @throws
     */
    public function time($field)
    {
        $this->addField('time', $field, ['null' => false]);

        return $this;
    }

    /**
     * datetime
     *
     * @param string $field
     * @return TablePrinter
     * @throws
     */
    public function dateTime($field)
    {
        $this->addField('datetime', $field, ['null' => false]);

        return $this;
    }

    /**
     * timestamp
     *
     * @return TablePrinter
     * @throws
     */
    public function timestamps()
    {
        $this->addField('timestamp', 'created_at', [
            'null' => true,
            'default' => 'CURRENT_TIMESTAMP'
        ]);

        $this->addField(
            'timestamp',
            'updated_at',
            [
            'null' => true,
            'default' => 'CURRENT_TIMESTAMP'
            ]
        );

        return $this;
    }

    /**
     * longint
     *
     * @param string $field
     * @param int    $size
     *
     * @return TablePrinter
     */
    public function longInteger($field, $size = 20)
    {
        return $this->loadWhole('longint', $field, $size);
    }

    /**
     * @param string $field
     * @param int    $size
     * @return TablePrinter
     * @throws ModelException
     */
    public function character($field, $size = 1)
    {
        if ($size > 4294967295) {
            throw new ModelException('Max size is 4294967295', E_USER_ERROR);
        }

        return $this->loadWhole('char', $field, $size);
    }

    /**
     * @param string $field
     * @param array  $enums
     * @return TablePrinter
     * @throws
     */
    public function enumerate($field, array $enums)
    {
        return $this->addField('enum', $field, ['value' => $enums]);
    }

    /**
     * autoincrement
     *
     * @param  string $field
     * @throws ModelException
     * @return TablePrinter
     */
    public function increment($field = null)
    {
        if (is_string($field)) {
            $this->autoincrement = (object) [
                'method' => 'int',
                'field' => $field
            ];

            return $this->integer($field)->primary();
        }

        if ($this->autoincrement !== false) {
            return $this;
        }

        if ($this->lastField === null) {
            return $this;
        }

        if (!in_array($this->lastField->method, ['int', 'longint', 'bigint', 'mediumint', 'smallint', 'tinyint'])) {
            throw new ModelException('Cannot add autoincrement to ' . $this->lastField->method, 1);
        }

        $this->autoincrement = $this->lastField;

        $this->dataBind[$this->lastField->field]['auto'] = true;

        return $this;
    }

    /**
     * primary
     *
     * @param  string|array $field
     * @throws ModelException
     * @return TablePrinter
     */
    public function primary($field = null)
    {
        if ($this->primary !== null) {
            throw new ModelException('Primary key has already defined', E_ERROR);
        }

        if (!is_null($field)) {
            return $this->addField('int', $field, [
                'null' => false,
                'auto' => true
            ]);
        }

        return $this->addIndexes('primary');
    }

    /**
     * indexe
     *
     * @return TablePrinter
     * @throws
     */
    public function indexe()
    {
        return $this->addIndexes('indexe');
    }

    /**
     * unique
     *
     * @return TablePrinter
     * @throws
     */
    public function unique()
    {
        return $this->addIndexes('unique');
    }

    /**
     * addIndexes crée un clause index sur le champs spécifié.
     *
     * @param  string $indexType
     * @throws ModelException
     * @return TablePrinter
     */
    private function addIndexes($indexType)
    {
        if ($this->lastField === null) {
            throw new ModelException('Cannot assign {$indexType}. Because field are not defined.', E_ERROR);
        }

        $last = $this->lastField;

        $this->rangs[$last->field]['data'][$indexType] = true;

        return $this;
    }

    /**
     * addField
     *
     * @param  string $method
     * @param  string $field
     * @param  array  $data
     * @return TablePrinter
     */
    private function addField($method, $field, array $data)
    {
        $method = strtolower($method);

        if (!$this->fields->has($method)) {
            $this->fields->push(new Collection, $method);
        }

        if ($this->getAutoincrement() instanceof \stdClass) {
            if ($this->getAutoincrement()->field == $field) {
                $data['auto'] = true;
            }
        }

        // Verifie l'existance d'un champs
        if ($this->fields->get($method)->has($field)) {
            return $this;
        }

        // default index are at false
        $data['primary'] = false;
        $data['unique']  = false;
        $data['indexe']  = false;

        // Permet de rendre les champs unique.
        $this->fields->get($method)->push(true, $field);

        $this->lastField = (object) [
            'method' => $method,
            'field'  => $field
        ];

        $this->rangs[$field] = ['type' => $method, 'data' => $data];

        return $this;
    }

    /**
     * loadWhole
     *
     * @param string $method
     * @param string $field
     * @param int    $size
     *
     * @return TablePrinter
     */
    private function loadWhole($method, $field, $size = 20)
    {
        $this->addField($method, $field, [
            'size' => $size,
            'null' => false
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
     * @return Collection
     */
    public function getFieldsRangs()
    {
        return new Collection($this->rangs);
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
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return string
     */
    public function getCollate()
    {
        return $this->collate;
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
     * @return TablePrinter
     */
    public function setAutoincrement($value)
    {
        $this->autoincrement = $value;

        return $this;
    }

    /**
     * __call
     *
     * @param  string $method
     * @param  array  $args
     * @throws \ErrorException
     */
    public function __call($method, $args)
    {
        throw new \ErrorException('Call to undefined method ' . static::class . '::'.$method.'()');
    }
}
