<?php

namespace Vertex\Vertex\Framework;


interface CommandInterface {
    public function run();
    public function commandName();
    public function setApp($app);
} 