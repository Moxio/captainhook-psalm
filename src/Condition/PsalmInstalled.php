<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\Condition;

use CaptainHook\App\Console\IO;
use CaptainHook\App\Hook\Condition;
use SebastianFeldmann\Git\Repository;

final class PsalmInstalled implements Condition
{
    public function isTrue(IO $io, Repository $repository): bool
    {
        $psalmBin = str_replace("/", DIRECTORY_SEPARATOR, "./vendor/bin/psalm");
        return is_file($psalmBin);
    }
}
