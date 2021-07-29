<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\PsalmConfig;

class XmlLoader implements Loader
{
    public function getConfigForProject(string $projectRootDir): Config
    {
        $configFile = $projectRootDir . "/psalm.xml";
        if (file_exists($configFile)) {
            return $this->loadConfigFile($configFile);
        }

        $distConfigFile = $projectRootDir . "/psalm.xml.dist";
        if (file_exists($distConfigFile)) {
            return $this->loadConfigFile($distConfigFile);
        }

        return new MissingConfig();
    }

    private function loadConfigFile(string $configFile): Config
    {
        $configDocument = new \DOMDocument();
        $configDocument->load($configFile);

        return new XmlConfig($configDocument);
    }
}
