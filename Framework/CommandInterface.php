<?php

namespace Vertex\Framework;


interface CommandInterface {
    /**
     * @return void
     */
    public function run();

    /**
     * @return String
     */
    public function commandName();

    /**
     * @param $app Application
     * @return void
     */
    public function setApp($app);

    /**
     * @param $i int
     * @return mixed
     */
    public function arg($i);

    /**
     * @return String
     */
    public function description();
} 