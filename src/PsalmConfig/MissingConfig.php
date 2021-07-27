<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\PsalmConfig;

class MissingConfig implements Config
{
    public function belongsToProjectFiles(string $relativePath): bool
    {
        return true;
    }
}
