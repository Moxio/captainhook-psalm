<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\PsalmConfig;

interface Config
{
    public function belongsToProjectFiles(string $relativePath): bool;
}
