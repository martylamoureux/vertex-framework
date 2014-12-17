<?php

namespace Vertex\Framework\Commands;

use Vertex\Framework\Command;
use Vertex\Framework\CommandInterface;

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