<?php

namespace Vertex\Framework\Commands;


use Vertex\Framework\Command;
use Vertex\Framework\CommandInterface;
use Vertex\Framework\Modeling\Model;
use Vertex\Framework\Modeling\ModelField;

class DatabaseUpdate extends Command implements CommandInterface
{

    /**
     *
     */
    public function run()
    {
        $modelName = $this->getParameter('Entity');

        $dbStructure = $this->app->db->getSchema();

        if (!class_exists(Model::getFullModelName($modelName))) {
            $this->stop("The model \"" . $modelName . "\" does not exists !");
        }

        /** @var Model $model */
        $model = Model::create($modelName);
        $schema = $model->getSchema();
        $tableName = $model->getTableName();

        $queries = [];

        if (!array_key_exists($tableName, $dbStructure)) {
            $queries = $schema->tableCreationQuery($tableName);
        } else {

            $structure = $dbStructure[$tableName];

            $distFields = [];
            foreach ($structure as $dbField) {
                $distFields[$dbField['Field']] = ModelField::fromDatabase($dbField);
            }

            $fieldsToUpdate = [];

            // Search missing fields in db
            /** @var ModelField $field */
            foreach ($schema->fields as $field) {
                $found = false;

                if (!$field->isSkippedInUpdate()) {
                    foreach ($structure as $dbField) {
                        if ($dbField['Field'] == $field->getName()) {
                            $fieldsToUpdate[] = $field;
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $queries = array_merge($queries, $field->alterationQuery($tableName));
                    }
                } elseif ($field->isManyToMany()) {
                    // Search for ManyToMany

                    // Test if relation table exists
                    if (!array_key_exists($field->getOption('__m2m'), $dbStructure)) {
                        $queries = array_merge($queries, $field->manyToManyTableCreationQuery($model));
                    }
                }
            }

            // Search for field not existing in model
            foreach ($structure as $dbField) {
                $found = false;
                foreach ($schema->fields as $field) {
                    if ($dbField['Field'] == $field->getName()) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    /** @var ModelField $distField */
                    $distField = $distFields[$dbField['Field']];
                    $queries = array_merge($queries, $distField->deletionQuery($tableName));
                }
            }

            // Check for changes (NO RENAME !!)
            foreach ($fieldsToUpdate as $field) {
                foreach ($structure as $dbField) {
                    if ($dbField['Field'] == $field->getName())
                        break;
                }

                /** @var ModelField $distField */
                $distField = $distFields[$dbField['Field']];

                if (!$field->equals($distField))
                    $queries = array_merge($queries, $field->alterationQuery($tableName, 'MODIFY'));

                if ($field->isUnique() && !$distField->isUnique())
                    $queries = array_merge($queries, [$field->uniqueConstraintQuery($tableName)]);

                if (!$field->isUnique() && $distField->isUnique())
                    $queries = array_merge($queries, [$field->dropUniqueConstraintQuery($tableName)]);

                if ($field->isForeignKey() && !$distField->isForeignKey())
                    $queries = array_merge($queries, [$field->foreignConstraintQuery($tableName)]);

                if (!$field->isForeignKey() && $distField->isForeignKey())
                    $queries = array_merge($queries, [$field->dropForeignConstraintQuery($tableName)]);

                if ($field->isPrimaryKey() && !$distField->isPrimaryKey())
                    $queries = array_merge($queries, [$field->primaryConstraintQuery($tableName)]);

                if (!$field->isPrimaryKey() && $distField->isPrimaryKey())
                    $queries = array_merge($queries, [$field->dropPrimaryConstraintQuery($tableName)]);
            }
        }

        //var_dump($queries);

        if ($this->hasArg('--run')) {
            $ok = 0;
            foreach ($queries as $key => $query) {
                if (!$this->app->db->success($query)) {
                    $this->red();
                    $this->displayLine("An error occured when executing : ");
                    $this->resetColor();
                    $this->displayLine(($key + 1) . ".    " . $query);
                    break;
                } else
                    $ok++;
            }
            $this->green();
            $this->displayLine("Update finished ");
            $this->resetColor();
            $this->displayLine($ok . " queries executed");
        } else {
            if (count($queries) == 0)
                $this->displayLine("Nothing to update !");
            else
                $this->displayLine("These queries will be executed : ");
            foreach ($queries as $key => $query)
                $this->displayLine(($key + 1) . ".    " . $query);
        }
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

    public function parameters()
    {
        $this->declareParameter('Entity', "Name of the entity", false);
        $this->declareFlag('run', "Execute the changes in the database");
    }
}