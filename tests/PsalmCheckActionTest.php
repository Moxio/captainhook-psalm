<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\Test;

use CaptainHook\App\Config;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Console\IO\NullIO;
use CaptainHook\App\Exception\ActionFailed;
use Moxio\CaptainHook\Psalm\PsalmCheckAction;
use Moxio\CaptainHook\Psalm\PsalmConfig\Config as PsalmConfig;
use Moxio\CaptainHook\Psalm\PsalmConfig\Loader as PsalmConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SebastianFeldmann\Cli\Command\Result;
use SebastianFeldmann\Cli\Processor;
use SebastianFeldmann\Git\Operator\Index;
use SebastianFeldmann\Git\Repository;

final class PsalmCheckActionTest extends TestCase
{
    private const REPOSITORY_ROOT = "/path/to/repo";

    /** @var MockObject&Processor */
    private $processor;
    /** @var MockObject&PsalmConfig */
    private $psalmConfig;
    /** @var PsalmCheckAction */
    private $psalmCheckAction;
    /** @var MockObject&Config */
    private $config;
    /** @var MockObject&Repository */
    private $repository;
    /** @var MockObject&Index */
    private $indexOperator;
    /** @var IO */
    private $io;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(Processor::class);
        $psalmConfigLoader = $this->createStub(PsalmConfigLoader::class);
        $this->psalmCheckAction = new PsalmCheckAction($this->processor, $psalmConfigLoader);

        $this->config = $this->createStub(Config::class);
        $this->repository = $this->createStub(Repository::class);
        $this->indexOperator = $this->createMock(Index::class);
        $this->repository
            ->method("getIndexOperator")
            ->willReturn($this->indexOperator);
        $this->repository
            ->method("getRoot")
            ->willReturn(self::REPOSITORY_ROOT);

        $this->psalmConfig = $this->createStub(PsalmConfig::class);
        $psalmConfigLoader
            ->method("getConfigForProject")
            ->willReturnMap([[self::REPOSITORY_ROOT, $this->psalmConfig]]);

        $this->io = new NullIO();
    }

    public function testReturnsWhenNoPhpFilesWereStaged(): void
    {
        $this->indexOperator->expects($this->once())
            ->method("hasStagedFilesOfType")
            ->with("php")
            ->willReturn(false);
        $this->processor->expects($this->never())
            ->method("run");

        $io = new NullIO();
        $configAction = new Config\Action(PsalmCheckAction::class);

        $this->psalmCheckAction->execute($this->config, $io, $this->repository, $configAction);
    }

    public function testReturnsWhenNoneOfTheStagedPhpFilesBelongToProjectFiles(): void
    {
        $this->indexOperator->expects($this->once())
            ->method("hasStagedFilesOfType")
            ->with("php")
            ->willReturn(true);
        $this->indexOperator->expects($this->once())
            ->method("getStagedFilesOfType")
            ->with("php")
            ->willReturn([ "foo.php", "bar.php" ]);
        $this->psalmConfig
            ->method("belongsToProjectFiles")
            ->willReturnMap([
                [ "foo.php", false ],
                [ "bar.php", false ],
            ]);
        $this->processor->expects($this->never())
            ->method("run");

        $io = new NullIO();
        $configAction = new Config\Action(PsalmCheckAction::class);

        $this->psalmCheckAction->execute($this->config, $io, $this->repository, $configAction);
    }

    public function testReturnsWhenOneOrMorePhpFilesWereStagedAndPsalmProcessWasSuccessfull(): void
    {
        $this->indexOperator->expects($this->once())
            ->method("hasStagedFilesOfType")
            ->with("php")
            ->willReturn(true);
        $this->indexOperator->expects($this->once())
            ->method("getStagedFilesOfType")
            ->with("php")
            ->willReturn([ "foo.php", "bar.php" ]);
        $this->psalmConfig
            ->method("belongsToProjectFiles")
            ->willReturn(true);

        $io = new NullIO();
        $configAction = new Config\Action(PsalmCheckAction::class);
        $expectedCmd = str_replace("/", DIRECTORY_SEPARATOR, "./vendor/bin/psalm 'foo.php' 'bar.php'");
        $this->processor->expects($this->once())
            ->method("run")
            ->with($this->equalTo($expectedCmd))
            ->willReturn(new Result($expectedCmd, 0));

        $this->psalmCheckAction->execute($this->config, $io, $this->repository, $configAction);
    }

    public function testThrowsActionFailedWhenPsalmReturnsErrorCode2(): void
    {
        $this->indexOperator->expects($this->once())
            ->method("hasStagedFilesOfType")
            ->with("php")
            ->willReturn(true);
        $this->indexOperator->expects($this->once())
            ->method("getStagedFilesOfType")
            ->with("php")
            ->willReturn([ "foo.php", "bar.php" ]);
        $this->psalmConfig
            ->method("belongsToProjectFiles")
            ->willReturn(true);

        $io = $this->io;
        $configAction = new Config\Action(PsalmCheckAction::class);
        $expectedCmd = str_replace("/", DIRECTORY_SEPARATOR, "./vendor/bin/psalm 'foo.php' 'bar.php'");
        $this->processor->expects($this->once())
            ->method("run")
            ->with($this->equalTo($expectedCmd))
            ->willReturn(new Result($expectedCmd, 2));

        $this->expectException(ActionFailed::class);
        $this->psalmCheckAction->execute($this->config, $io, $this->repository, $configAction);
    }

    public function testThrowsRuntimeExceptionWhenPsalmReturnsErrorCode1(): void
    {
        $this->indexOperator->expects($this->once())
            ->method("hasStagedFilesOfType")
            ->with("php")
            ->willReturn(true);
        $this->indexOperator->expects($this->once())
            ->method("getStagedFilesOfType")
            ->with("php")
            ->willReturn([ "foo.php", "bar.php" ]);
        $this->psalmConfig
            ->method("belongsToProjectFiles")
            ->willReturn(true);

        $io = $this->io;
        $configAction = new Config\Action(PsalmCheckAction::class);
        $expectedCmd = str_replace("/", DIRECTORY_SEPARATOR, "./vendor/bin/psalm 'foo.php' 'bar.php'");
        $this->processor->expects($this->once())
            ->method("run")
            ->with($this->equalTo($expectedCmd))
            ->willReturn(new Result($expectedCmd, 1));

        $this->expectException(\RuntimeException::class);
        $this->psalmCheckAction->execute($this->config, $io, $this->repository, $configAction);
    }

    public function testOnlyChecksFilesThatBelongToProjectFiles(): void
    {
        $this->indexOperator->expects($this->once())
            ->method("hasStagedFilesOfType")
            ->with("php")
            ->willReturn(true);
        $this->indexOperator->expects($this->once())
            ->method("getStagedFilesOfType")
            ->with("php")
            ->willReturn([ "foo.php", "bar.php" ]);
        $this->psalmConfig
            ->method("belongsToProjectFiles")
            ->willReturnMap([
                [ "foo.php", false ],
                [ "bar.php", true ],
            ]);

        $io = new NullIO();
        $configAction = new Config\Action(PsalmCheckAction::class);
        $expectedCmd = str_replace("/", DIRECTORY_SEPARATOR, "./vendor/bin/psalm 'bar.php'");
        $this->processor->expects($this->once())
            ->method("run")
            ->with($this->equalTo($expectedCmd))
            ->willReturn(new Result($expectedCmd, 0));

        $this->psalmCheckAction->execute($this->config, $io, $this->repository, $configAction);
    }
}
