<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\PsalmConfig;

interface Loader
{
    public function getConfigForProject(string $projectRootDir): Config;
}
