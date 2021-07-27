<?php
declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\Test\PsalmConfig;

use Moxio\CaptainHook\Psalm\PsalmConfig\XmlConfig;
use PHPUnit\Framework\TestCase;

class XmlConfigTest extends TestCase
{
    public function testExcludesAllFilesIfNoProjectFilesAreSpecified(): void
    {
        $config = $this->createConfig("");

        $this->assertFalse($config->belongsToProjectFiles("src/Foo/Bar.php"));
        $this->assertFalse($config->belongsToProjectFiles("Baz.php"));
    }

    public function testIncludesFilesInSpecifiedDirectories(): void
    {
        $projectFilesContent = <<<XML
<directory name="src" />
<directory name="test/Foo" />
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertTrue($config->belongsToProjectFiles("src/Bar.php"));
        $this->assertTrue($config->belongsToProjectFiles("src/Foo/Bar.php"));
        $this->assertTrue($config->belongsToProjectFiles("test/Foo/Bar.php"));
        $this->assertTrue($config->belongsToProjectFiles("test/Foo/Bar/Baz.php"));
    }

    public function testExcludesFilesNotInSpecifiedDirectories(): void
    {
        $projectFilesContent = <<<XML
<directory name="src" />
<directory name="test/Foo" />
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertFalse($config->belongsToProjectFiles("resources/Bar.php"));
        $this->assertFalse($config->belongsToProjectFiles("test/Baz/Bar.php"));
        $this->assertFalse($config->belongsToProjectFiles("src2/Bar.php"));
        $this->assertFalse($config->belongsToProjectFiles("test/Foos/Bar.php"));
    }

    public function testIncludesSpecifiedFiles(): void
    {
        $projectFilesContent = <<<XML
<file name="src/Bar.php" />
<file name="test/Foo/Bar.php" />
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertTrue($config->belongsToProjectFiles("src/Bar.php"));
        $this->assertTrue($config->belongsToProjectFiles("test/Foo/Bar.php"));
    }

    public function testExcludesFilesNotSpecified(): void
    {
        $projectFilesContent = <<<XML
<file name="src/Bar.php" />
<file name="test/Foo/Bar.php" />
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertFalse($config->belongsToProjectFiles("resources/Bar.php"));
        $this->assertFalse($config->belongsToProjectFiles("test/Foo/Baz.php"));
        $this->assertFalse($config->belongsToProjectFiles("src/Bar.phpt"));
        $this->assertFalse($config->belongsToProjectFiles("test/Foo/Bar2.php"));
    }

    public function testExcludesFilesInIgnoreFilesDirectories(): void
    {
        $projectFilesContent = <<<XML
<directory name="src" />
<directory name="test/Foo" />
<ignoreFiles>
    <directory name="src/Foo" />
    <directory name="test/Foo/Bar" />
</ignoreFiles>
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertFalse($config->belongsToProjectFiles("src/Foo/Bar.php"));
        $this->assertFalse($config->belongsToProjectFiles("test/Foo/Bar/Baz.php"));
    }

    public function testExcludesFilesInIgnoreFilesFiles(): void
    {
        $projectFilesContent = <<<XML
<directory name="src" />
<directory name="test/Foo" />
<ignoreFiles>
    <file name="src/Foo/Bar.php" />
    <file name="test/Foo/Bar/Baz.php" />
</ignoreFiles>
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertFalse($config->belongsToProjectFiles("src/Foo/Bar.php"));
        $this->assertFalse($config->belongsToProjectFiles("test/Foo/Bar/Baz.php"));
    }

    public function testDoesNotExcludeFilesNotMatchingIgnoreFilesBlock(): void
    {
        $projectFilesContent = <<<XML
<directory name="src" />
<directory name="test/Foo" />
<ignoreFiles>
    <file name="src/Foo/Baz.php" />
    <directory name="test/Foo/Bar2" />
</ignoreFiles>
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertTrue($config->belongsToProjectFiles("src/Foo/Bar.php"));
        $this->assertTrue($config->belongsToProjectFiles("test/Foo/Bar/Baz.php"));
    }

    public function testDoesNotCareAboutDifferentDirectorySeparators(): void
    {
        $projectFilesContent = <<<XML
<file name="src/Bar.php" />
<file name="test\\Foo\\Bar.php" />
XML;
        $config = $this->createConfig($projectFilesContent);

        $this->assertTrue($config->belongsToProjectFiles("src\\Bar.php"));
        $this->assertTrue($config->belongsToProjectFiles("test/Foo/Bar.php"));
    }

    private function createConfig(string $projectFilesContent): XmlConfig
    {
        $fullContent = <<<XML
<?xml version="1.0"?>
<psalm xmlns="https://getpsalm.org/schema/config">
    <projectFiles>
        $projectFilesContent
    </projectFiles>
</psalm>
XML;
        $document = new \DOMDocument();
        $document->loadXml($fullContent);

        return new XmlConfig($document);
    }
}
