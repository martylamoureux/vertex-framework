<?php

namespace Vertex\Vertex\Framework;


class Command implements CommandInterface {

    protected $app;

    public function run()
    {

    }

    public function commandName()
    {

    }

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

}