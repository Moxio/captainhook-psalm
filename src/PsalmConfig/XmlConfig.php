<?php
declare(strict_types=1);

namespace Moxio\CaptainHook\Psalm\PsalmConfig;

/**
 * Provides a wrapper around a Psalm XML config.
 *
 * Note that this class is not feature complete, but works on a 'best effort' basis for
 * the most common use cases. PR's with improvements are welcome.
 */
class XmlConfig implements Config
{
    private const XMLNS = "https://getpsalm.org/schema/config";

    /** @var \DOMDocument */
    private $document;

    public function __construct(\DOMDocument $document)
    {
        $this->document = $document;
    }

    public function belongsToProjectFiles(string $relativePath): bool
    {
        $normalizedRelativePath = $this->normalizePath($relativePath);

        $projectFilesElements = $this->document->getElementsByTagNameNS(self::XMLNS, "projectFiles");
        foreach ($projectFilesElements as $projectFilesElement) {
            $directoryElements = $projectFilesElement->getElementsByTagNameNS(self::XMLNS, "directory");
            foreach ($directoryElements as $directoryElement) {
                $directoryName = $directoryElement->getAttribute("name");
                $normalizedDirectoryPath = $this->normalizePath($directoryName);
                if (strpos($normalizedRelativePath, $normalizedDirectoryPath . "/") === 0) {
                    return true;
                }
            }

            $fileElements = $projectFilesElement->getElementsByTagNameNS(self::XMLNS, "file");
            foreach ($fileElements as $fileElement) {
                $fileName = $fileElement->getAttribute("name");
                $normalizedFilePath = $this->normalizePath($fileName);
                if ($normalizedRelativePath === $normalizedFilePath) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        return strtr($path, '/\\', '//');
    }
}
