<?php
/**
 * Created by PhpStorm.
 * User: martylamoureux
 * Date: 16/12/14
 * Time: 17:34
 */

namespace Vertex\Framework\Modeling;


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

    public function textField($name) {
        $field = new ModelField($name);
        $field->setType('longtext');
        $this->fields[] = $field;
        return $field;
    }

    public function foreignKeyField($modelName, $fieldName = NULL, $dbFieldName = NULL) {
        if ($fieldName === NULL)
            $fieldName = strtolower($modelName);

        if ($dbFieldName === NULL)
            $dbFieldName = $fieldName.'_id';

        /** @var Model $model */
        $model = Model::create($modelName);
        $field = new ModelField($dbFieldName);
        $field->setType('integer')->unsigned()
            ->addOption('__fk', $model->getTableName())
            ->addOption('__fk_id', $model->idField)
            ->addOption('__fk_field', $fieldName)
            ->addOption('__fk_model', $modelName);
        $this->fk[] = $dbFieldName;
        $this->fields[] = $field;
        return $field;
    }

    public function inversedForeignKey($modelName, $fieldName = NULL, $KeyFieldName = NULL) {
        if ($fieldName === NULL)
            $fieldName = strtolower($modelName);
        if ($KeyFieldName === NULL)
            $KeyFieldName = $fieldName.'_id';

        /** @var Model $model */
        $model = Model::create($modelName);
        $field = new ModelField($fieldName);
        $field->setType('integer')->unsigned()
            ->addOption('__ifk', $fieldName)
            ->addOption('__ifk_field', $KeyFieldName)
            ->addOption('__ifk_model', $modelName);
        $this->fk[] = $fieldName;
        $this->fields[] = $field;
        return $field;
    }

    public function manyToManyField($modelName, $fieldName = NULL, $table = NULL) {
        if ($fieldName === NULL)
            $fieldName = strtolower($modelName);

        /** @var Model $model */
        $model = Model::create($modelName);

        $modelName = $model->getModelName();
        $thisModelName = $this->model->getModelName();

        if ($table === NULL) {
            if ($modelName < $thisModelName)
                $table = $modelName . '_' . $thisModelName;
            else
                $table = $thisModelName . '_' . $modelName;
        }
        $model = Model::create($modelName);
        $field = new ModelField($modelName);
        $field->setType('integer')->unsigned()
            ->addOption('__m2m', $table)
            ->addOption('__m2m_model', $modelName)
            ->addOption('__m2m_field', $fieldName);
        $this->fields[] = $field;
        return $field;
    }

    public function tableCreationQuery($tableName) {
        $query = "CREATE TABLE ".$tableName." (";
        $fields = [];
        $afterQueries = [];
        /** @var ModelField $field */
        foreach ($this->fields as $field) {
            if (!$field->isSkippedInUpdate())
                $fields[] = $field->tableCreation();
            if ($field->hasOption('unique'))
                $afterQueries[] = $field->uniqueConstraintQuery($tableName);
            if ($field->hasOption('__fk'))
                $afterQueries[] = $field->foreignConstraintQuery($tableName);
            if ($field->isManyToMany())
                $afterQueries = array_merge($afterQueries, $field->manyToManyTableCreationQuery($this->model));
        }
        $query .= implode(', ', $fields).')';
        return array_merge([$query], $afterQueries);
    }

} 