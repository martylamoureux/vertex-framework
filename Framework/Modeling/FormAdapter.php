<?php


namespace Vertex\Framework\Modeling;


use Vertex\Framework\Application;
use Vertex\Framework\Input;

class FormAdapter {

    /**
     * @var Model
     */
    private $model;

    /**
     * @var ModelSchema
     */
    private $schema;

    private $fields = [];

    private $formAction = ".";
    private $formMethod = "POST";

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->schema = $model->getSchema();
    }

    /**
     * @param string $formAction
     */
    public function setAction($controller, $action, $params = [])
    {
        /** @var Application $app */
        global $app;
        $this->formAction = $app->generateUrl($controller, $action, $params);
    }

    /**
     * @param string $formMethod
     */
    public function setMethod($formMethod)
    {
        $this->formMethod = $formMethod;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    public function addField($fieldName, $fieldType, $title, $value = NULL, $params = []) {
        if (array_key_exists('attributes', $params) && array_key_exists('class', $params['attributes']))
            unset($params['attributes']['class']);

        if (array_key_exists('attributes', $params) && array_key_exists('id', $params['attributes']))
            unset($params['attributes']['id']);

        if (array_key_exists($fieldName, $this->fields))
            return;

        if ($fieldType == 'string')
            $fieldType = 'text';
        if ($fieldType == 'longtext')
            $fieldType = 'textarea';

        $this->fields[$fieldName] = [
            'name' => $fieldName,
            'title' => $title,
            'type' => $fieldType,
            'value' => $value,
            'params' => $params
        ];

        return $this;
    }

    public function addModelField($fieldName, $params = []) {
        /** @var ModelField $field */
        foreach ($this->schema->fields as $field) {
            if ($field->getName() == $fieldName) {
                $type = $field->getType();
                $this->addField($fieldName, $type, $field->getTitle(), $this->model->get($fieldName), $params);
            } else if ($field->isForeignKey() && $field->getOption('__fk_field') == $fieldName) {

                if (!array_key_exists('choices', $params)) {
                    $repo = new Repository($field->getOption('__fk_model'));
                    $params['choices'] = $repo->query()->getList();
                } elseif ($params['choices'] instanceof QueryBuilder) {
                    $params['choices'] = $params['choices']->getList();
                }
                $this->addField($field->getName(), 'select', $field->getTitle(), $this->model->get($field->getName()), $params);
            }
        }
    }

    public function renderField($fieldName, $params = [])
    {
        if (array_key_exists($fieldName, $this->fields)) {
            $field = $this->fields[$fieldName];
        } else {
            $field = ['type' => 'text', 'params' => []];
        }

        global $app;

        $attributes = "";
        if (array_key_exists('attributes', $params)) {
            foreach ($params['attributes'] as $attr => $value)
                $attributes .= ' ' . $attr . '="' . $value . '"';
        }
        $value = $field['value'];
        return $app->frameworkTwig->render('FormAdapter/fields/'.$field['type'].'.html.twig', compact('field','params', 'attributes', 'value'));
    }

    public function renderFields()
    {
        /** @var Application $app */
        global $app;
        $res = [];
        /** @var ModelField $field */
        foreach ($this->fields as $fieldName => $fieldProps) {
            $params = $fieldProps['params'];
            $res[] = $this->renderField($fieldName, $params);
        }
        return implode("\r\n", $res);
    }

    public function render() {
        /** @var Application $app */
        global $app;

        return $app->frameworkTwig->render('FormAdapter/form.html.twig', [
            'action'=>$this->formAction,
            'method' => $this->formMethod,
            'model' => $this->model,
            'fields' => $this->renderFields(),
        ]);
    }

    public function renderFormOpening() {
        return '<form action="'.$this->formAction.'" method="'.$this->formMethod.'">';
    }

    public function hydrateModel() {
        $attributes = [];
        foreach ($this->fields as $field) {
            $attributes[$field['name']] = Input::get($field['name']);
        }

        $this->model->hydrate($attributes, false);
        return $this->model;
    }



} 