<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/05/2018
 * Time: 18:28
 */

namespace BeardedByte;

class ModelManager {

    /**
     * @var \PDO
     */
    public $database;
    public $table_name = "";
    public $structure = [];

    /**
     * ModelManager constructor.
     *
     * @param \PDO $database
     */
    public function __construct($database) {
        $this->database = $database;
        $this->get_structure();
    }

    /** Utils */

    /**
     * Return the structure of the table
     */
    protected function get_structure() {
        $output = $this->database->query("PRAGMA table_info($this->table_name)");
        $this->structure = $output->fetchAll(\PDO::FETCH_ASSOC);
    }


    /** Requests builder */

    /**
     * @return string
     */
    protected function build_insert_request() {
        $sql = "INSERT INTO $this->table_name VALUES(";
        $acc = 0;
        foreach ($this->structure as $column) {
            $acc += 1;
            if ($acc > 1) {
                $sql .= ",";
            }
            $sql .= ":".$column['name'];

        }
        $sql .= ")";

        return $sql;
    }

    /**
     * @param $model
     *
     * @return string
     */
    protected function build_select_request($model) {
        $sql = "SELECT * FROM $this->table_name";
        if (count($model) > 0) {
            $sql .= " WHERE ";
            $acc = 0;
            foreach ($this->structure as $column) {
                if (isset($model[$column['name']])) {
                    $acc += 1;
                    if ($acc > 1) {
                        $sql .= " AND ";
                    }
                    $sql .= $column['name']." = :".$column['name'];
                }
            }
        }
        return $sql;
    }

    /**
     * @return string
     */
    protected function build_update_request() {
        $sql = "UPDATE $this->table_name SET ";
        $acc = 0;
        foreach ($this->structure as $column) {
            if ($column['name'] != 'id') {
                $acc += 1;
                if ($acc > 1) {
                    $sql .= ",";
                }
                $sql .= $column['name']." = :".$column['name'];
            }
        }
        $sql .= " WHERE id = :id";
        return $sql;
    }


    /** Basic requests */

    /**
     * @param $model
     *
     * @throws ModelManagerException
     */
    public function insert($model) {
        $sql = $this->build_insert_request();

        // id is auto-incremented
//        if (isset($model['id'])) {
//            unset($model['id']);
//        }

        $request = $this->database->prepare($sql);
        if (!$request->execute($model)) {
            throw new ModelManagerException('Une erreur est survenue');
        };
    }

    /**
     * @param $model
     * Model is an array with only the fields you want the search to be based on
     * e.g. $array('id' => 1)
     *
     * @return array
     * @throws ModelManagerException
     */
    public function select($model) {
        $sql = $this->build_select_request($model);
        $request = $this->database->prepare($sql);
        if (!$request->execute($model)) {
            throw new ModelManagerException('Une erreur est survenue');
        };

        return $request->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $model
     * The update selection is based on the id
     *
     * @throws ModelManagerException
     */
    public function update($model) {
        $sql = $this->build_update_request();

        $request = $this->database->prepare($sql);
        if (!$request->execute($model)) {
            throw new ModelManagerException('Une erreur est survenue');
        };
    }

    /**
     * @param $model
     * The delete selection is based on the id
     *
     * @throws ModelManagerException
     */
    public function delete($model) {

        $_model = ['id' => $model['id']];

        $sql = "DELETE FROM $this->table_name WHERE id = :id";

        $request = $this->database->prepare($sql);
        if (!$request->execute($_model)) {
            throw new ModelManagerException('Une erreur est survenue');
        };
    }


    /** Get From DB */

    /**
     * @return array
     * @throws ModelManagerException
     */
    public function get_all() {
        return $this->select(array());
    }

    /**
     * @param $id
     *
     * @return array
     * @throws ModelManagerException
     */
    public function get($id) {
        $users = $this->select(array('id' => $id));

        if (count($users) == 1) {
            return $users[0];
        }

        return null;
    }


    /** Local function */

    /**
     * Create an empty model
     * @return array
     */
    public function create_model() {
        $model = [];
        foreach ($this->structure as $column) {
            switch ($column['type']) {
                case 'TEXT':
                    $model[$column['name']] = '';
                    break;
                case 'INTEGER':
                    $model[$column['name']] = 0;
                    break;
                case 'REAL':
                    $model[$column['name']] = 0.0;
                    break;
                default:
                    $model[$column['name']] = '';
                    break;
            }
        }

        return $model;
    }

    /**
     * @param $array
     * @param null $fields
     * Create a model with the data from an array
     * You can limit the fields filled from the array
     *
     * @return array
     */
    public function build_model_from($array, $fields = null) {

        $model = $this->create_model();

        foreach ($this->structure as $column) {

            if (is_array($fields) && !in_array($column['name'], $fields)) {
                continue;
            }

            if (isset($array[$column['name']])) {
                $value = $array[$column['name']];
                switch ($column['type']) {
                    case 'TEXT':
                        $value = strval(htmlentities($value));
                        break;
                    case 'INTEGER':
                        $value = intval($value);
                        break;
                    case 'REAL':
                        $value = doubleval($value);
                        break;
                    default:
                        $value = strval(htmlentities($value));
                        break;
                }
                $model[$column['name']] = $value;
            }
        }
        return $model;
    }

    /**
     * @param $model
     * @param $array
     * @param null $fields
     * Update a model with the data from an array
     * You can limit the fields filled from the array
     */
    public function update_model_from(&$model, $array, $fields = null) {

        foreach ($this->structure as $column) {

            if (is_array($fields) && !in_array($column['name'], $fields)) {
                continue;
            }

            if (isset($array[$column['name']])) {
                $value = $array[$column['name']];
                switch ($column['type']) {
                    case 'TEXT':
                        $value = strval(htmlentities($value));
                        break;
                    case 'INTEGER':
                        $value = intval($value);
                        break;
                    case 'REAL':
                        $value = doubleval($value);
                        break;
                    default:
                        $value = strval(htmlentities($value));
                        break;
                }
                $model[$column['name']] = $value;
            }
        }
    }

    /**
     * @param $model
     *
     * @return bool
     */
    public function validate($model) {
        return true;
    }
}


class ModelManagerException extends \Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}