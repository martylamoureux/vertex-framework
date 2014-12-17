<?php
/**
 * Created by PhpStorm.
 * User: martylamoureux
 * Date: 16/12/14
 * Time: 17:34
 */

namespace Vertex\Vertex\Framework\Modeling;


class ModelSchema {

    /**
     * @var Model
     */
    private $model;

    public $fields;
    public $pk;

    public function __construct(Model $model) {
        $this->model = $model;
        $this->fields = [];
        $this->pk = [];
        $this->fk = [];
    }

    public function stringField($name, $length = 255) {
        $field = new ModelField($name);
        $field->setType('string')->setLength($length);
        $this->fields[] = $field;
        return $field;
    }

    public function integerField($name) {
        $field = new ModelField($name);
        $field->setType('integer');
        $this->fields[] = $field;
        return $field;
    }

    public function floatField($name) {
        $field = new ModelField($name);
        $field->setType('float');
        $this->fields[] = $field;
        return $field;
    }

    public function booleanField($name) {
        $field = new ModelField($name);
        $field->setType('boolean');
        $this->fields[] = $field;
        return $field;
    }

    public function dateField($name) {
        $field = new ModelField($name);
        $field->setType('date');
        $this->fields[] = $field;
        return $field;
    }

    public function dateTimeField($name) {
        $field = new ModelField($name);
        $field->setType('datetime');
        $this->fields[] = $field;
        return $field;
    }

    public function idField($name = 'id') {
        $field = new ModelField($name);
        $field->setType('integer')->unsigned()->addOption('__pk');
        $this->pk[] = $name;
        $this->fields[] = $field;
        return $field;
    }

    public function passwordField($name) {
        $field = new ModelField($name);
        $field->setType('string')->setLength(255)->addOption('password');
        $this->fields[] = $field;
        return $field;
    }

    public function foreingKeyField($modelName, $fieldName = NULL) {
        if ($fieldName === NULL)
            $fieldName = strtolower($modelName).'_id';

        $field = new ModelField($fieldName);
        $field->setType('integer')->unsigned()->addOption('__fk');
        $this->fk[] = $fieldName;
        $this->fields[] = $field;
        return $field;
    }

} 