<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
// @codingStandardsIgnoreFile
namespace Magento\SemanticVersionChecker;

use PHPSemVerChecker\Filter\SourceFilter;

class FileChangeDetector
{
    private $sourceBeforeDir;
    private $sourceAfterDir;

    public function __construct($sourceBeforeDir, $sourceAfterDir)
    {
        $this->sourceBeforeDir = $sourceBeforeDir;
        $this->sourceAfterDir = $sourceAfterDir;
    }

    public function getChangedFiles()
    {
        $sourceBeforeFiles = self::getFiles([$this->sourceBeforeDir], '*', true);
        $sourceAfterFiles = self::getFiles([$this->sourceAfterDir], '*', true);
        $identicalFilter = new SourceFilter();
        $identicalFilter->filter($sourceBeforeFiles, $sourceAfterFiles);
        return array_merge($sourceAfterFiles, $sourceBeforeFiles);
    }

    public function getFiles(array $dirPatterns, $fileNamePattern, $recursive = true)
    {
        $result = [];
        foreach ($dirPatterns as $oneDirPattern) {
            $oneDirPattern = str_replace('\\', '/', $oneDirPattern);
            $entriesInDir = Glob::glob("{$oneDirPattern}/{$fileNamePattern}", Glob::GLOB_NOSORT | Glob::GLOB_BRACE);
            $subDirs = Glob::glob("{$oneDirPattern}/*", Glob::GLOB_ONLYDIR | Glob::GLOB_NOSORT | Glob::GLOB_BRACE);
            $filesInDir = array_diff($entriesInDir, $subDirs);

            if ($recursive) {
                $filesInSubDir = self::getFiles($subDirs, $fileNamePattern);
                $result = array_merge($result, $filesInDir, $filesInSubDir);
            }
        }
        return $result;
    }
}
