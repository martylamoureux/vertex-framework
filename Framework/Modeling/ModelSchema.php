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

    public function __construct(Model $model) {
        $this->model = $model;
        $this->fields = [];
    }

    public function stringField($name, $length = 255) {
        $field = new ModelField($name);
        $field->setLength(255);
        $this->fields[] = $field;
        return $field;
    }

} 