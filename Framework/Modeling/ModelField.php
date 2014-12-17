<?php
/**
 * Created by PhpStorm.
 * User: martylamoureux
 * Date: 16/12/14
 * Time: 17:37
 */

namespace Vertex\Framework\Modeling;


class ModelField {
    private $name;
    private $title;
    private $type;
    private $length;
    private $options;

    function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $option
     */
    public function addOption($option)
    {
        $this->options[] = $option;
        return $this;
    }

    public function hasOption($option) {
        return array_key_exists($option, $this->options);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function nullable() {
        $this->addOption("nullable");
        return $this;
    }

    public function unsigned() {
        $this->addOption('unsigned');
        return $this;
    }

    public function defaultValue($value)
    {
        $this->addOption('default:' . $value);
        return $this;
    }

    public function unique() {
        $this->addOption('unique');
        return $this;
    }

    public function onUpdate($action) {
        if (!$this->hasOption('__fk'))
            return $this;
        $this->addOption('onupdate:'.$action);
    }

    public function onDelete($action) {
        if (!$this->hasOption('__fk'))
            return $this;
        $this->addOption('ondelete:'.$action);
    }
} 