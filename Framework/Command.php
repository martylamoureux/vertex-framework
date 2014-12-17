<?php

namespace Vertex\Vertex\Framework;


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

}