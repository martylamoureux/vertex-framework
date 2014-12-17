<?php

namespace Vertex\Framework\Commands;


use Vertex\Framework\Command;
use Vertex\Framework\CommandInterface;

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