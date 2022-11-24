<?php
declare(strict_types=1);

use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;

class PsalmPlugin implements AfterFileAnalysisInterface
{
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void
    {
        // "league/flysystem": "^1.0"
        if (interface_exists(\League\Flysystem\FilesystemAdapter::class)) {
            $ignoreFile = __DIR__ . '/src/Gaufrette/Adapter/Flysystem.php';
        } else {
            $ignoreFile = __DIR__ . '/src/Gaufrette/Adapter/FlysystemV2V3.php';
        }
        if ($event->getFileStorage()->file_path === $ignoreFile) {
            $issues = IssueBuffer::getIssuesData();
            IssueBuffer::clear();
            unset($issues[$ignoreFile]);
            IssueBuffer::addIssues($issues);
        }
    }
}
