<?php

namespace Vertex\Framework;


interface CommandInterface {
    public function run();
    public function commandName();
    public function setApp($app);
    public function arg($i);
} 