<?php
namespace Bow\Database\QueryBuilder;

use Bow\Exception\QueryBuilderException;

trait  QueryBuilderTrait
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $classname;

    /**
     * @var string
     */
    protected $select;

    /**
     * @var string
     */
    protected $where;

    /**
     * @var string
     */
    protected $join;

    /**
     * @var string
     */
    protected $limit;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var string
     */
    protected $havin;

    /**
     * @var string
     */
    protected $order;
    
    /**
     * @var array
     */
    private $where_data_dind = [];

    /**
     * select, ajout de champ à séléction.
     *
     * SELECT $column | SELECT column1, column2, ...
     *
     * @param array $select
     *
     * @return  QueryBuilderTrait
     */
    public function select(array $select = ['*'])
    {
        if (count($select) == 0) {
            return $this;
        }

        if (count($select) == 1 && $select[0] == '*') {
            $this->select = '*';
        } else {
            $this->select = '`' . implode('`, `', $select) . '`';
        }

        return $this;
    }

    /**
     * where, ajout condition de type where, si chainé ajout un <<and>>
     *
     * WHERE column1 $comp $value|column
     *
     * @param $column
     * @param $comp
     * @param null $value
     * @param $boolean
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function where($column, $comp = '=', $value, $boolean = 'and')
    {
        if (! $this->isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = '=';
        }

        // Ajout de matcher sur id.
        if ($comp == '=' && $value === null) {
            $value = $column;
            $column = 'id';
        }

        if ($value === null) {
            throw new  QueryBuilderException('Valeur de comparaison non définir', E_ERROR);
        }

        if (!in_array(Str::lower($boolean), ['and', 'or'])) {
            throw new  QueryBuilderException('Le booléen '. $boolean . ' non accepté', E_ERROR);
        }

        $this->where_data_dind[$column] = $value;

        if ( $this->where == null) {
            $this->where = $column . ' ' . $comp . ' :' . $column;
        } else {
            $this->where .= ' ' . $boolean .' '. $column . ' '. $comp .' :'. $column;
        }

        return $this;
    }

    /**
     * orWhere, retourne une condition de type [where colonne = value <<or colonne = value>>]
     *
     * @param string $column
     * @param string $comp
     * @param null $value
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function orWhere($column, $comp = '=', $value)
    {
        if (is_null( $this->where)) {
            throw new  QueryBuilderException('Cette fonction ne peut pas être utiliser sans un where avant.', E_ERROR);
        }

        $this->where($column, $comp, $value, 'or');

        return $this;
    }

    /**
     * clause where avec comparaison en <<is null>>
     *
     * WHERE column IS NULL
     *
     * @param string $column
     * @param string $boolean='and'
     *
     * @return  QueryBuilderTrait
     */
    public function whereNull($column, $boolean = 'and')
    {
        if (!is_null( $this->where)) {
            $this->where = '`' . $column . '` is null';
        } else {
            $this->where = ' ' . $boolean .' `' . $column .'` is null';
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<not null>>
     *
     * WHERE column NOT NULL
     *
     * @param $column
     * @param string $boolean='and|or'
     *
     * @return  QueryBuilderTrait
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        if (is_null( $this->where)) {
            $this->where = '`'. $column . '` is not null';
        } else {
            $this->where .= ' ' . $boolean .' `' . $column .'` is not null';
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<between>>
     *
     * WHERE column BETWEEN '' AND ''
     *
     * @param $column
     * @param array $range
     * @param string boolean='and|or'
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function whereBetween($column, array $range, $boolean = 'and')
    {

        if (count($range) !== 2) {
            throw new  QueryBuilderException('Le paramètre 2 ne doit pas être un  QueryBuilderTraitau vide.', E_ERROR);
        }

        $between = implode(' and ', $range);

        if (is_null( $this->where)) {
            if ($boolean == 'not') {
                $this->where = '`' . $column.'` not between ' . $between;
            } else {
                $this->where = '`' . $column . '` between ' . $between;
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and `'.$column .'`  not between ' . $between;
            } else {
                $this->where .= ' ' . $boolean . ' `' . $column. '` between ' . $between;
            }
        }

        return $this;
    }

    /**
     * WHERE column NOT BETWEEN '' AND ''
     *
     * @param $column
     * @param $range
     * @return  QueryBuilderTrait
     */
    public function whereNotBetween($column, array $range)
    {
        $this->whereBetween($column, $range, 'not');

        return $this;
    }

    /**
     * clause where avec comparaison en <<in>>
     *
     * @param string $column
     * @param array $range
     * @param string $boolean
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function whereIn($column, array $range, $boolean = 'and')
    {
        if (count($range) > 2) {
            $range = array_slice($range, 0, 2);
        } else {

            if (count($range) == 0) {
                throw new  QueryBuilderException('Le paramètre 2 ne doit pas être un  QueryBuilderTraitau vide.', E_ERROR);
            }

            $range = [$range[0], $range[0]];
        }

        $in = implode(', ', $range);

        if (is_null( $this->where)) {
            if ($boolean == 'not') {
                $this->where = '`' . $column . '` not in ('.$in.')';
            } else {
                $this->where = '`' . $column .'` in ('.$in.')';
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and `' . $column . '` not in ('.$in.')';
            } else {
                $this->where .= ' and `'.$column.'` in ('.$in.')';
            }
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<not in>>
     *
     * @param string $column
     * @param array $range
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function whereNotIn($column, array $range)
    {
        $this->whereIn($column, $range, 'not');
        return $this;
    }

    /**
     * clause join
     *
     * @param $column
     *
     * @return  QueryBuilderTrait
     */
    public function join($column)
    {
        if (is_null( $this->join)) {
            $this->join = 'inner join `'.$column.'`';
        } else {
            $this->join .= ', `'.$column.'`';
        }

        return $this;
    }

    /**
     * clause left join
     *
     * @param $column
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function leftJoin($column)
    {
        if (is_null( $this->join)) {
            $this->join = 'left join `'.$column.'`';
        } else {
            if (!preg_match('/^(inner|right)\sjoin\s.*/', $this->join)) {
                $this->join .= ', `'.$column.'`';
            } else {
                throw new  QueryBuilderException('La clause inner join est dèja initalisé.', E_ERROR);
            }
        }

        return $this;
    }

    /**
     * clause right join
     *
     * @param $column
     * @throws  QueryBuilderException
     * @return  QueryBuilderTrait
     */
    public function rightJoin($column)
    {
        if (is_null( $this->join)) {
            $this->join = 'right join `'.$column.'`';
        } else {
            if (!preg_match('/^(inner|left)\sjoin\s.*/', $this->join)) {
                $this->join .= ', `'.$column.'`';
            } else {
                throw new  QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
            }
        }
        return $this;
    }

    /**
     * On, Si chainé avec lui même doit ajouter un <<and>> avant, sinon
     * si chainé avec <<orOn>> orOn ajout un <<or>> dévant
     *
     * @param string $column1
     * @param string $comp
     * @param string $column2
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function on($column1, $comp = '=', $column2)
    {
        if (is_null( $this->join)) {
            throw new  QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (! $this->isComporaisonOperator($comp)) {
            $column2 = $comp;
        }

        if (!preg_match('/on/i', $this->join)) {
            $this->join .= ' on `' . $column1 . '` ' . $comp . ' `' . $column2 . '`';
        }

        return $this;
    }

    /**
     * clause On, suivie d'une combinaison par un comparateur <<or>>
     * Il faut que l'utilisateur fasse un <<on()>> avant d'utiliser le <<orOn>>
     *
     * @param string $column
     * @param string $comp
     * @param string $value
     *
     * @throws  QueryBuilderException
     *
     * @return  QueryBuilderTrait
     */
    public function orOn($column, $comp = '=', $value)
    {
        if (is_null( $this->join)) {
            throw new  QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (! $this->isComporaisonOperator($comp)) {
            $value = $comp;
        }

        if (preg_match('/on/i', $this->join)) {
            $this->join .= ' or `'.$column.'` '.$comp.' '.$value;
        } else {
            throw new  QueryBuilderException('La clause <b>on</b> n\'est pas initialisé.', E_ERROR);
        }

        return $this;
    }

    /**
     * clause group by
     *
     * @param string $column
     *
     * @return  QueryBuilderTrait
     */
    public function group($column)
    {
        if (is_null( $this->group)) {
            $this->group = $column;
        }

        return $this;
    }

    /**
     * clause having, s'utilise avec un groupBy
     *
     * @param string $column
     * @param string $comp
     * @param null $value
     * @param string $boolean
     */
    public function having($column, $comp = '=', $value, $boolean = 'and')
    {
        if (! $this->isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = '=';
        }
        if (is_null( $this->havin)) {
            $this->havin = '`'.$column.'` '.$comp.' '.$value;
        } else {
            $this->havin .= ' '.$boolean.' `'.$column.'` '.$comp.' '.$value;
        }
    }

    /**
     * clause order by
     *
     * @param string $column
     * @param string $type
     *
     * @return  QueryBuilderTrait
     */
    public function orderBy($column, $type = 'asc')
    {
        if (is_null( $this->order)) {
            if (!in_array($type, ['asc', 'desc'])) {
                $type = 'asc';
            }

            $this->order = 'order by `'.$column.'` '.$type;
        }

        return $this;
    }

    /**
     * jump = offset
     *
     * @param int $offset
     * @return  QueryBuilderTrait
     */
    public function jump($offset = 0)
    {
        if (is_null( $this->limit)) {
            $this->limit = $offset.', ';
        }
        return $this;
    }

    /**
     * take = limit
     *
     * @param int $limit
     *
     * @return  QueryBuilderTrait
     */
    public function take($limit)
    {
        if (is_null( $this->limit)) {
            $this->limit = $limit;
            return $this;
        }

        if (preg_match('/^([\d]+),$/', $this->limit, $match)) {
            array_shift($match);
            $this->limit = $match[0].', '.$limit;
        }

        return $this;
    }

    /**
     * Utilitaire isComporaisonOperator, permet valider un opérateur
     *
     * @param string $comp Le comparateur logique
     *
     * @return bool
     */
    private function isComporaisonOperator($comp)
    {
        return in_array($comp, ['=', '>', '<', '>=', '=<', '<>', '!=', 'LIKE', 'like'], true);
    }

    /**
     * Formate la requete select
     *
     * @return string
     */
    private function getSelectStatement()
    {
        $sql = 'select ';

        // Ajout de la clause select
        if (is_null( $this->select)) {
            $sql .= '* from `' . $this->table .'`';
        } else {
            $sql .= $this->select . ' from `' . $this->table . '`';
            $this->select;
        }

        // Ajout de la clause join
        if (!is_null( $this->join)) {
            $sql .= ' join ' . $this->join;
            $this->join;
        }

        // Ajout de la clause where
        if (!is_null( $this->where)) {
            $sql .= ' where ' . $this->where;
            $this->where;
        }

        // Ajout de la clause order
        if (!is_null( $this->order)) {
            $sql .= ' ' . $this->order;
            $this->order;
        }

        // Ajout de la clause limit
        if (!is_null( $this->limit)) {
            $sql .= ' limit ' . $this->limit;
            $this->limit;
        }

        // Ajout de la clause group
        if (!is_null( $this->group)) {
            $sql .= ' group by ' . $this->group;
            $this->group;

            if (!is_null( $this->havin)) {
                $sql .= ' having ' . $this->havin;
            }
        }

        return $sql;
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getSelectStatement();
    }
}