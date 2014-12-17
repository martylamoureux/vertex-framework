<?php

namespace Vertex\Framework;


class Command {

    /**
     * @var Application
     */
    protected $app;

    public function display($text) {
        echo $text."\r\n";
    }

    /**
     * @param mixed $app
     */
    public function setApp($app)
    {
        $this->app = $app;
    }

    public function arg($i) {
        global $argv;
        return $argv[2+$i];
    }

}