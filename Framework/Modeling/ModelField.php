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
    private $default;

    function __construct($name)
    {
        $this->name = $name;
        $this->default = NULL;
        $this->options = [];
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
    public function addOption($option, $val=true)
    {
        $this->options[$option] = $val;
        return $this;
    }

    public function hasOption($option) {
        return array_key_exists($option, $this->options);
    }

    public function getOption($option) {
        if (!$this->hasOption($option))
            return NULL;
        return $this->options[$option];
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

    /**
     * @return null
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function getParsedDefault() {
        if ($this->type == 'string')
            return "'".$this->default."'";
        else
            return $this->default;
    }

    /**
     * @param null $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
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
        $this->setDefault($value);
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

    public static function fromDatabase($dbField) {
        $field = new ModelField($dbField['Field']);
        $typeParts = explode('(', $dbField['Type']);
        $type = $typeParts[0];

        if ($type == 'varchar')
            $type = 'string';
        elseif ($type == 'int')
            $type = 'integer';

        $length = str_replace(')', '', $typeParts[1]);
        if (strpos($length,'unsigned') !== false) {
            $length = str_replace(' unsigned', '', $length);
            $field->unsigned();
        }
        if ($dbField['Null'] == 'YES')
            $field->nullable();
        if ($dbField['Key'] == 'PRI')
            $field->addOption('__pk');
        if ($dbField['Key'] == 'UNI')
            $field->unique();
        if ($dbField['Key'] == 'MUL') {
            $field->addOption('__fk', $dbField['fk_table']);
            $field->addOption('__fk_id', $dbField['fk_id']);
        }
        if ($dbField['Default'] !== NULL)
            $field->defaultValue($dbField['Default']);
        $field->setType($type)->setLength($length);
        return $field;
    }

    /**
     * @param $field ModelField
     * @return bool
     */
    public function equals($field) {
        if ($this->getLength() === NULL || $field->getLength() === NULL)
            $length = true;
        else
            $length = $this->getLength() == $field->getLength();

        return ($this->getName() == $field->getName() &&
            $this->getType() == $field->getType() &&
            $length &&
            $this->hasOption('__fk') == $field->hasOption('__fk') &&
            $this->hasOption('unsigned') == $field->hasOption('unsigned') &&
            $this->hasOption('nullable') == $field->hasOption('nullable')
        );
    }

    private function fieldProperties() {
        $type = $this->type;
        if ($type == 'string')
            $type = "varchar";
        else if ($type == 'integer')
            $type = 'int';

        $type = strtoupper($type);

        if ($this->length !== NULL)
            $type .= '('.$this->length.')';

        if ($this->hasOption('unsigned'))
            $type .= ' UNSIGNED';

        if ($this->hasOption('__pk'))
            $type .= ' primary KEY AUTO_INCREMENT';

        $null = (!$this->hasOption('nullable') ? ' NOT NULL' : '');
        $default = ($this->getDefault() !== NULL ? ' DEFAULT '.$this->getParsedDefault() : '');
        return $type.$null.$default;
    }

    public function alterationQuery($tableName, $action = 'ADD') {
        $queries = [];

        $queries[] = "ALTER TABLE ".$tableName." ".$action." ".$this->name.' '.$this->fieldProperties();
        if ($this->hasOption('unique'))
            $queries[] = $this->uniqueConstraintQuery($tableName);
        if ($this->hasOption('__fk') && $action == 'ADD')
            $queries[] = $this->foreignConstraintQuery($tableName);
        return $queries;
    }

    public function tableCreation() {
        return $this->name.' '.$this->fieldProperties();
    }

    public function foreignConstraintQuery($tableName) {
        return "ALTER TABLE ".$tableName." ADD CONSTRAINT fk_".$this->name." FOREIGN KEY (".$this->name.') references '.$this->getOption('__fk').'('.$this->getOption('__fk_id').')';
    }

    public function uniqueConstraintQuery($tableName) {
        return "ALTER IGNORE TABLE ".$tableName." ADD UNIQUE (".$this->name.')';
    }

    public function deletionQuery($tableName) {
        return ['ALTER TABLE '.$tableName.' DROP COLUMN '.$this->name];
    }
} 