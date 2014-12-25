<?php

namespace Vertex\Framework\Commands;


use Vertex\Framework\Command;
use Vertex\Framework\CommandInterface;

class PublishAssets extends Command implements CommandInterface {

    public function run()
    {
        $alias = $this->getParameter('Module Alias');
        /** @var \Vertex\Framework\Module $module */
        $module = $this->app->$alias;
        if ($module === NULL)
            $this->stop("Unable to find this module");

        if (count($module->getAssets()) == 0) {
            $this->green();
            $this->displayLine("No assets to publish for this module");
            return;
        }

        $destRoot = APP_ROOT . DIRECTORY_SEPARATOR . $module->assetPath(true);

        foreach ($module->getAssets() as $asset => $publicAsset) {
            $source = APP_ROOT . DIRECTORY_SEPARATOR . $asset;
            $destination = $destRoot . DIRECTORY_SEPARATOR . $publicAsset;

            $this->createDirectory($destRoot . DIRECTORY_SEPARATOR . dirname($publicAsset));

            $res = copy($source, $destination);
            if (!$res)
                $this->stop("Unable to copy " . $asset);

            $this->green();
            $this->displayLine("Copied : " . $asset);
        }
    }

    public function commandName()
    {
        return "module:publish-assets";
    }
    
    public function description()
    {
        return "Copy the module's assets to the project public folder";
    }
    
    public function parameters() {
        $this->declareParameter('Module Alias', "Alias given in modules.php of the module", false);
    }
}