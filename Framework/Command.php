<?php

namespace Vertex\Framework;


class Command
{

    /**
     * @var Application
     */
    protected $app;

    private $args = [];
    private $nextParamPosition = 0;

    public function commandName()
    {
    }

    public function description()
    {
    }

    public function display($text)
    {
        echo $text;
    }

    private function setColor($code)
    {
        echo "\033[" . $code . "m";
    }

    public function red()
    {
        $this->setColor(31);
    }

    public function green()
    {
        $this->setColor(32);
    }

    public function yellow()
    {
        $this->setColor(33);
    }

    public function blue()
    {
        $this->setColor(34);
    }

    public function cyan()
    {
        $this->setColor(36);
    }

    public function white()
    {
        $this->setColor(37);
    }

    public function resetColor()
    {
        echo "\033[0m";
    }

    public function displayLine($text = "")
    {
        echo $text . "\r\n";
    }

    public function copyFile($source, $destination)
    {
        return copy($source, $destination);
    }

    public function createDirectory($path)
    {
        if (is_dir($path)) return true;
        $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
        $return = $this->createDirectory($prev_path);
        return ($return && is_writable($prev_path)) ? mkdir($path) : false;
    }

    /**
     * @param mixed $app
     */
    public function setApp($app)
    {
        $this->app = $app;
        $this->args = [];
        $this->parameters();
    }

    public function stop($errorMessage = "")
    {
        $this->displayLine();
        $this->red();
        $this->displayLine(">>> The execution has been stopped by the command");
        $this->displayLine('    ' . $errorMessage);
        $this->displayLine();
        $this->resetColor();
        exit();
    }

    public function arg($i)
    {
        global $argv;
        return $argv[2 + $i];
    }

    protected function hasArg($arg)
    {
        global $argv;
        return in_array($arg, $argv);
    }

    public function parameters()
    {

    }

    public function isUsageCorrect()
    {

    }

    public function displayUsage()
    {

        $this->displayLine();

        $this->display(" ");
        $this->yellow();
        $this->display($this->commandName());
        $this->green();

        $params = [];
        $optionalParams = [];
        $flags = [];

        $maxLength = 0;

        foreach ($this->args as $arg) {
            if ($arg['type'] == 'parameter' && !$arg['optional'])
                $params[] = $arg;
            elseif ($arg['type'] == 'parameter' && $arg['optional'])
                $optionalParams[] = $arg;
            else
                $flags[] = $arg;
            $len = strlen($arg['name']);
            if ($len > $maxLength)
                $maxLength = $len;
        }

        $maxTabs = floor($maxLength / 4) + 1;

        foreach ($params as $arg)
            $this->display(" " . $arg['name']);
        foreach ($optionalParams as $arg)
            $this->display(" [" . $arg['name'] . ']');
        foreach ($flags as $arg)
            $this->display(" [--" . $arg['name'] . ']');

        $this->displayLine();
        $this->displayLine();

        $this->resetColor();
        $this->display(" " . $this->description());

        $this->displayLine();
        $this->displayLine();

        foreach ($params as $arg) {
            $nbTabs = $maxTabs - (strlen($arg['name']) / 4);
            $tabs = "";
            for ($i = 0; $i < $nbTabs; $i++)
                $tabs .= "\t";
            $this->cyan();
            $this->display(" " . $arg['name']);
            $this->resetColor();
            $this->display(":" . $tabs . $arg['description']);
            $this->displayLine();

        }
        foreach ($optionalParams as $arg) {
            $nbTabs = $maxTabs - (strlen($arg['name']) / 4);
            $tabs = "";
            for ($i = 0; $i < $nbTabs; $i++)
                $tabs .= "\t";
            $this->cyan();
            $this->display(" " . $arg['name']);
            $this->resetColor();
            $this->display(": " . $tabs . $arg['description']);
            $this->yellow();
            $this->display(" (Optional)");
            $this->displayLine();
        }
        foreach ($flags as $arg) {
            $nbTabs = $maxTabs - ((strlen($arg['name']) + 2) / 4);
            $tabs = "";
            for ($i = 0; $i < $nbTabs; $i++)
                $tabs .= "\t";
            $this->cyan();
            $this->display(" --" . $arg['name']);
            $this->resetColor();
            $this->display(": " . $tabs . $arg['description']);
            $this->displayLine();
        }

        $this->displayLine();

    }

    protected function declareParameter($paramName, $description, $optional = true)
    {
        $this->args[] = [
            'name' => $paramName,
            'type' => 'parameter',
            'description' => $description,
            'optional' => $optional,
            'position' => $this->nextParamPosition,
        ];
        $this->nextParamPosition++;
    }

    protected function declareFlag($flagName, $description)
    {
        $this->args[] = [
            'name' => $flagName,
            'type' => 'flag',
            'description' => $description,
        ];
    }

    protected function getParameter($paramName)
    {
        foreach ($this->args as $param) {
            if ($param['type'] == 'parameter' && $param['name'] == $paramName) {
                return $this->arg($param['position']);
            }
        }
        return NULL;
    }

}