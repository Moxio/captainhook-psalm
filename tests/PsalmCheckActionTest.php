<?php

declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\Test;

use CaptainHook\App\Config;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Console\IO\NullIO;
use CaptainHook\App\Exception\ActionFailed;
use Moxio\CaptainHook\Psalm\PsalmCheckAction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SebastianFeldmann\Cli\Command\Result;
use SebastianFeldmann\Cli\Processor;
use SebastianFeldmann\Git\Operator\Index;
use SebastianFeldmann\Git\Repository;

final class PsalmCheckActionTest extends TestCase
{
    /** @var MockObject&Processor */
    private $processor;
    /** @var PsalmCheckAction */
    private $psalmCheckAction;
    /** @var MockObject&Config */
    private $config;
    /** @var MockObject&Repository */
    private $repository;
    /** @var MockObject&Index */
    private $indexOperator;
    /** @var IO  */
    private $io;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(Processor::class);
        $this->psalmCheckAction = new PsalmCheckAction($this->processor);

        $this->config = $this->createMock(Config::class);
        $this->repository = $this->createMock(Repository::class);
        $this->indexOperator = $this->createMock(Index::class);
        $this->repository->expects($this->any())
            ->method("getIndexOperator")
            ->willReturn($this->indexOperator);

        $this->io = new NullIO();
    }

    public function testReturnsWhenNoPhpFilesWereStaged(): void
    {
        $this->indexOperator->expects($this->once())
            ->method("hasStagedFilesOfType")
            ->with("php")
            ->willReturn(false);

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
}
