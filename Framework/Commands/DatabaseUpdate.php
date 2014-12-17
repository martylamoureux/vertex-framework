<?php

namespace Vertex\Framework\Commands;


use Vertex\Framework\Command;
use Vertex\Framework\CommandInterface;
use Vertex\Framework\Modeling\Model;

class DatabaseUpdate extends Command implements CommandInterface {

    public function run()
    {
        $structure = $this->app->db->getSchema();
        $model = Model::create($this->arg(0));
        var_dump($model);
    }

    public function commandName()
    {
        return "database:update";
    }
}