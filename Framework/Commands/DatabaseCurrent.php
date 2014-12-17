<?php

namespace Vertex\Vertex\Framework\Commands;


use Vertex\Vertex\Framework\Command;
use Vertex\Vertex\Framework\CommandInterface;

class DatabaseCurrent extends Command implements CommandInterface {

    public function run()
    {
        $this->app->db->getSchema();
    }

    public function commandName()
    {
        return "schema:current";
    }
}