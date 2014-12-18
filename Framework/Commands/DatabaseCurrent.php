<?php

namespace Vertex\Framework\Commands;


use Vertex\Framework\Command;
use Vertex\Framework\CommandInterface;
use Vertex\Framework\Modeling\ModelField;

class DatabaseCurrent extends Command implements CommandInterface {

    public function run()
    {
        $schema = $this->app->db->getSchema();
        foreach ($schema as $table => $fields) {
            $this->cyan();
            $this->displayLine("Table \"".$table."\"");
            $this->yellow();
            foreach ($fields as $field) {
                $distField = ModelField::fromDatabase($field);
                $this->displayLine("   ".$distField->getName().' '.$distField->fieldProperties());
            }
        }
    }

    public function commandName()
    {
        return "schema:current";
    }

    /**
     * @return String
     */
    public function description()
    {
        return "Show the current structure of the database";
    }

    public function parameters() {

    }
}