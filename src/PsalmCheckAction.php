<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm;

use CaptainHook\App\Config;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Exception\ActionFailed;
use CaptainHook\App\Hook\Action;
use Moxio\CaptainHook\Psalm\PsalmConfig\Loader;
use Moxio\CaptainHook\Psalm\PsalmConfig\XmlLoader;
use SebastianFeldmann\Cli\Processor;
use SebastianFeldmann\Cli\Processor\ProcOpen;
use SebastianFeldmann\Git\Repository;

final class PsalmCheckAction implements Action
{
    /** @var Loader */
    private $psalmConfigLoader;
    /** @var Processor */
    private $processor;

    public function __construct(Processor $processor = null, Loader $psalmConfigLoader = null)
    {
        $this->processor = $processor ?? new ProcOpen();
        $this->psalmConfigLoader = $psalmConfigLoader ?? new XmlLoader();
    }

    public function execute(Config $config, IO $io, Repository $repository, Config\Action $action): void
    {
        $repositoryRootDir = $repository->getRoot();
        $psalmConfig = $this->psalmConfigLoader->getConfigForProject($repositoryRootDir);

        $indexOperator = $repository->getIndexOperator();

        if ($indexOperator->hasStagedFilesOfType("php") === false) {
            return;
        }
        $stagedPhpFiles = $indexOperator->getStagedFilesOfType("php");
        $checkedPhpFiles = array_filter($stagedPhpFiles, function (string $phpFile) use ($psalmConfig): bool {
            return $psalmConfig->belongsToProjectFiles($phpFile);
        });
        if (count($checkedPhpFiles) === 0) {
            return;
        }

        $psalmArgs = [];
        foreach ($checkedPhpFiles as $checkedPhpFile) {
            $psalmArgs[] = escapeshellarg($checkedPhpFile);
        }

        $psalmBin = str_replace("/", DIRECTORY_SEPARATOR, "./vendor/bin/psalm");
        $psalmResult = $this->processor->run($psalmBin . " " . implode(" ", $psalmArgs));

        if ($psalmResult->isSuccessful() === false) {
            if ($psalmResult->getCode() === 2) {
                $baseMessage = "Psalm found errors in files to be committed:";
                throw new ActionFailed($baseMessage . PHP_EOL . $psalmResult->getStdOut());
            } elseif ($psalmResult->getCode() === 1) {
                $baseMessage = "Failed to check files using Psalm:";
                throw new \RuntimeException($baseMessage . PHP_EOL . $psalmResult->getStdErr());
            } else {
                throw new \LogicException("Psalm returned with an unexpected code " . $psalmResult->getCode());
            }
        }
    }
}
