<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Feed\Generator;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Serialize\SerializerInterface;
use RunAsRoot\AgenticCommerceProtocol\Model\Feed\ProductFeedGenerator;

/**
 * Generates and stores static product feed files
 */
class StaticFeedGenerator
{
    private const FEED_DIRECTORY = 'acp';

    public function __construct(
        private readonly ProductFeedGenerator $feedGenerator,
        private readonly Filesystem $filesystem,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Generate static feed file and return metadata
     *
     * @param int $storeId Store ID to generate feed for
     * @param string $filename Output filename (default: feed.json)
     * @return array Metadata about generated feed
     * @throws FileSystemException
     */
    public function generate(int $storeId = 0, string $filename = 'feed.json'): array
    {
        // Generate feed data using existing generator
        $feedData = $this->feedGenerator->generate();

        // Add static feed metadata
        $feedData['feed_type'] = 'static';
        $feedData['store_id'] = $storeId;

        // Get writable directory
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        // Ensure directory exists
        $feedPath = self::FEED_DIRECTORY . '/' . $filename;
        $directory->create(self::FEED_DIRECTORY);

        // Write feed to file
        $jsonContent = $this->serializer->serialize($feedData);
        $directory->writeFile($feedPath, $jsonContent);

        // Get file stats
        $absolutePath = $directory->getAbsolutePath($feedPath);
        $fileSize = $directory->stat($feedPath)['size'] ?? 0;

        return [
            'product_count' => $feedData['total_count'] ?? 0,
            'file_size' => $this->formatBytes($fileSize),
            'file_size_bytes' => $fileSize,
            'path' => $absolutePath,
            'relative_path' => $feedPath,
            'generated_at' => $feedData['generated_at'] ?? date('c')
        ];
    }

    /**
     * Format bytes to human-readable size
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Check if static feed file exists
     */
    public function exists(string $filename = 'feed.json'): bool
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $feedPath = self::FEED_DIRECTORY . '/' . $filename;

        return $directory->isFile($feedPath);
    }

    /**
     * Get static feed file path
     */
    public function getFilePath(string $filename = 'feed.json'): string
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        return $directory->getAbsolutePath(self::FEED_DIRECTORY . '/' . $filename);
    }

    /**
     * Delete static feed file
     */
    public function delete(string $filename = 'feed.json'): bool
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $feedPath = self::FEED_DIRECTORY . '/' . $filename;

        if ($directory->isFile($feedPath)) {
            $directory->delete($feedPath);
            return true;
        }

        return false;
    }
}
