<?php

namespace Vertex\Framework;


class Command {

    /**
     * @var Application
     */
    protected $app;

    public function display($text) {
        echo $text;
    }

    private function setColor($code) {
        echo "\033[".$code."m";
    }

    public function green() {
        $this->setColor(32);
    }

    public function displayLine($text) {
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

    protected function hasArg($arg)
    {
        global $argv;
        return in_array($arg, $argv);
    }

}