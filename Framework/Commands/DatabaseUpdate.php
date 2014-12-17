<?php

namespace Vertex\Framework\Commands;


use Vertex\Framework\Command;
use Vertex\Framework\CommandInterface;
use Vertex\Framework\Modeling\Model;
use Vertex\Framework\Modeling\ModelField;

class DatabaseUpdate extends Command implements CommandInterface {

    /**
     *
     */
    public function run()
    {
        $dbStructure = $this->app->db->getSchema();
        /** @var Model $model */
        $model = Model::create($this->arg(0));
        $schema = $model->getSchema();

        $queries = [];

        if (!array_key_exists($model->getTableName(), $dbStructure)) {
            $queries = $this->createTable($model->getTableName(), $schema);
        } else {

            $structure = $dbStructure[$model->getTableName()];

            $fieldsToUpdate = [];

            // Search missing fields in db
            /** @var ModelField $field */
            foreach ($schema->fields as $field) {
                $found = false;
                foreach ($structure as $dbField) {
                    if ($dbField['Field'] == $field->getName()) {
                        $fieldsToUpdate[] = $field;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $queries = array_merge($queries, $field->alterationQuery($model->getTableName()));
                }
            }

            // Search for field not existing
            foreach ($structure as $dbField) {
                $found = false;
                foreach ($schema->fields as $field) {
                    if ($dbField['Field'] == $field->getName()) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $distField = ModelField::fromDatabase($dbField);
                    $queries = array_merge($queries, $distField->deletionQuery($model->getTableName()));
                }
            }

            // Check for changes (NO RENAME !!)
            foreach ($fieldsToUpdate as $field) {
                foreach ($structure as $dbField) {
                    if ($dbField['Field'] == $field->getName())
                        break;
                }
                $distField = ModelField::fromDatabase($dbField);
                if (!$field->equals($distField)) {
                    $queries = array_merge($queries, $field->alterationQuery($model->getTableName(), 'MODIFY'));
                }
            }
        }

        if ($this->hasArg('--run')) {
            $ok = 0;
            foreach ($queries as $key => $query) {
                if (!$this->app->db->success($query)) {
                    $this->displayLine("An error occured when executing : ");
                    $this->displayLine(($key + 1) . ".    " . $query);
                    break;
                } else
                    $ok++;
            }
            $this->displayLine("Update finished ");
            $this->displayLine($ok." queries executed");
        } else {
            if (count($queries) == 0)
                $this->displayLine("Nothing to update !");
            else
                $this->displayLine("These queries will be executed : ");
            foreach ($queries as $key => $query)
                $this->displayLine(($key + 1) . ".    " . $query);
        }
    }

    public function createTable($tableName, $schema) {
        $queries = [];
        $afterQueries = [];
        $query = "CREATE TABLE ".$tableName." (";
        $fields = [];
        /** @var ModelField $field */
        foreach ($schema->fields as $field) {
            $fields[] = $field->tableCreation();
            if ($field->hasOption('unique'))
                $afterQueries[] = $field->uniqueConstraintQuery($tableName);
            if ($field->hasOption('__fk'))
                $afterQueries[] = $field->foreignConstraintQuery($tableName);
        }
        $query .= implode(', ', $fields).')';
        $queries[] = $query;
        $queries = array_merge($queries, $afterQueries);
        return $queries;
    }

    public function commandName()
    {
        return "schema:update";
    }

    /**
     * @return String
     */
    public function description()
    {
        return "Generate the database structure for an Entity";
    }
}