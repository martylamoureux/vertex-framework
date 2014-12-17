<?php

namespace Vertex\Vertex\Framework\Commands;

use Vertex\Vertex\Framework\Command;
use Vertex\Vertex\Framework\CommandInterface;

class Test extends Command implements CommandInterface {

    public function run()
    {
        echo "OK";
    }

    public function commandName()
    {
        return "test";
    }
}