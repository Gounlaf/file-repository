<?php declare(strict_types=1);

namespace Service;

use \Closure;
use \DateTime;

use function Stringy\create;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Domain\Service\FileServingServiceInterface;
use Manager\StorageManager;
use Model\Entity\File;

/**
 * @inheritdoc
 */
class FileServingService implements FileServingServiceInterface
{
    const HASH_ALGORITHM                        = 'md4';
    const HTTP_HEADER_LAST_MODIFIER_DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

    /**
     * @var \Manager\StorageManager
     */
    private $storageManager;

    public function __construct(StorageManager $storageManager)
    {
        $this->storageManager = $storageManager;
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function buildResponse(File $file): Response
    {
        $directLinkAllowed = false;

        if ($this->storageManager->isStoredLocally($file)) {
            // Very useful for content stored locally; it will handle all headers
            return BinaryFileResponse::create($this->storageManager->getPath($file))
                // TODO: Disallow caching
                /*->setPrivate()*/
            ;
        } else {
            if ($directLinkAllowed) {
                // TODO: "direct link" to real storage is allow (from file-repo owner? from user who uploaded the file?)
                // Send a Redirect response
            } else {
                if ($this->shouldServe(
                    $file,
                    isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? new DateTime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : null,
                    isset($_SERVER['HTTP_IF_NONE_MATCH']) ? new DateTime($_SERVER['HTTP_IF_NONE_MATCH']) : null)
                ) {
                    return new StreamedResponse(
                        $this->buildClosure($file),
                        Response::HTTP_OK,
                        $this->buildOutputHeaders($file)
                    );
                } else {
                    return Response::create()->setNotModified();
                }
            }
        }
    }


    /**
     * Create a closure that will output self to the browser
     *
     * @param \Model\Entity\File $file
     *
     * @return \Closure
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function buildClosure(File $file): Closure
    {
        $storageManager = $this->storageManager;
        $storageManager->assertFileExist($file);

        return function () use ($file, $storageManager) {
            $fp = $storageManager->getFileStream($file);

            if (false == $fp) {
                exit();
            }

            fseek($fp, 0);
            print(fread($fp, 1024));// Send first bytes to browser
            flush();

            fpassthru($fp);
            flush();

            fclose($fp);
        };
    }

    /**
     * Basing on the headers passed from browser decide if we are going
     * to stream a new file, or just return ETag information
     *
     * @param \Model\Entity\File $file
     * @param \DateTime $modifiedSince
     * @param \DateTime $noneMatch
     *
     * @return bool
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function shouldServe(File $file, DateTime $modifiedSince, DateTime $noneMatch): bool
    {
        // Rely only on information stored in database
        // Requesting file metadata on storage might cost

        $currentModifiedSince = $file->getDateAdded()->format(self::HTTP_HEADER_LAST_MODIFIER_DATE_FORMAT);
        $currentETag          = $file->getContentHash();

        return ($modifiedSince !== $currentModifiedSince)
            || ($noneMatch !== $currentETag);
    }

    /**
     * Output headers to serve
     *
     * @param \Model\Entity\File $file
     *
     * @return array
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function buildOutputHeaders(File $file): array
    {
        // Rely only on information stored in database
        // Requesting file metadata on storage might cost

        return [
            'Content-Type'   => $file->getMimeType(),
            'Content-Length' => $file->getSize(),
            'Last-Modified'  => $file->getDateAdded()->format(self::HTTP_HEADER_LAST_MODIFIER_DATE_FORMAT),
            'ETag'           => $file->getContentHash(),
        ];
    }
}
