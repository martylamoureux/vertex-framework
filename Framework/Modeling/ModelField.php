<?php
/**
 * Created by PhpStorm.
 * User: martylamoureux
 * Date: 16/12/14
 * Time: 17:37
 */

namespace Vertex\Vertex\Framework\Modeling;


class ModelField {
    private $name;
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

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
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
    }

    public function unsigned() {
        $this->addOption('unsigned');
    }

    public function defaultValue($value)
    {
        $this->addOption('default:' . $value);
    }
} 