<?php declare(strict_types=1);

namespace Manager;

use \SplFileInfo;

use GuzzleHttp\Psr7\StreamWrapper;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException as FlysystemFileNotFoundException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Stringy\Stringy;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Exception\HttpDownloader\HttpDownloaderExceptionInterface;
use Exception\ImageManager\DirectoryNotFoundException;
use Exception\Flysystem\FileNotFoundException;
use Exception\Flysystem\SystemNotFoundException;
use Exception\Upload\UploadException;
use Model\Entity\File;
use Service\HttpFileDownloader;

/**
 * Manages where to put a new file
 * and tells if putting a new file is possible
 * ===========================================
 *
 * @package Manager
 */
class StorageManager
{
    /**
     * @var \Symfony\Component\Routing\Generator\UrlGenerator
     */
    protected $router;

    /**
     * @var \Service\HttpFileDownloader
     */
    protected $fileDownloader;

    /**
     * @var \League\Flysystem\FilesystemInterface[]
     */
    protected $flysystems;

    /**
     * @var string Where file are stored temporary, before being moved into final storage
     */
    protected $tmpPath;

    // TODO Remove me
    /** @var string $storagePath */
    private $storagePath;

    /**
     * @var string prefered hash algo (highest sha* available)
     */
    protected $hashAlgo;

    /**
     * @var string
     */
    protected $webUrl;

    /**
     * StorageManager constructor.
     *
     * @param \Service\HttpFileDownloader
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $router
     * @param array $flysystems
     * @param string $tmpPath
     * @param string $storagePath
     * @param string $hashAlgo
     * @param string $webUrl
     *
     * @throws \Exception\ImageManager\DirectoryNotFoundException
     */
    public function __construct(
        HttpFileDownloader $fileDownloader,
        UrlGenerator $router,
        array $flysystems,
        string $tmpPath,
        string $storagePath,
        string $hashAlgo,
        string $webUrl
    ) {
        $this->fileDownloader = $fileDownloader;
        $this->router         = $router;
        $this->flysystems     = $flysystems;
        $this->tmpPath        = realpath($tmpPath);
        $this->hashAlgo       = $hashAlgo;

        // TODO Remove me
        $this->storagePath = realpath($storagePath);

        $this->webUrl = $webUrl;


        // TODO Remove me
        if (!$this->storagePath) {
            throw new DirectoryNotFoundException('Storage path defined in "storage.path" configuration option does not exists');
        }

        if (false === $this->tmpPath) {
            throw new DirectoryNotFoundException('Temporary storage path defined in "storage.tmppath" configuration option does not exists');
        }
    }

    /**
     * @deprecated
     *
     * @param string $url
     * @param bool $withPrefix
     *
     * @return string
     */
    public function getFileName(string $url, $withPrefix = true)
    {
        $parts = explode('?', $url);
        $name  = '';

        if ($withPrefix == true) {
            $name .= substr(md5($url), 0, 8) . '-';
        }

        return $name . pathinfo($parts[0], PATHINFO_BASENAME);
    }

    /**
     * @deprecated
     *
     * @param string $url
     * @param bool $withPrefix
     *
     * @return string
     */
    public function getStorageFileName(string $url, $withPrefix = true)
    {
        if (is_file($this->storagePath . '/' . $url)) {
            return $url;
        }

        return $this->getFileName($url, $withPrefix);
    }

    /**
     * @deprecated
     *
     * @param string $url
     * @param bool $withPrefix
     *
     * @return string
     */
    public function getPathWhereToStoreTheFile(string $url, $withPrefix = true)
    {
        return $this->escapeTargetPath(
            $this->storagePath . '/' . $this->getStorageFileName($url, $withPrefix)
        );
    }

    /**
     * @deprecated
     *
     * @param string $url
     *
     * @return string
     */
    public function getUniquePathWhereToStorageFile(string $url)
    {
        $originalUrl = $url;

        while (is_file($this->getPathWhereToStoreTheFile($url))) {
            $url = rand(10000, 99999) . $originalUrl;
        }

        return $this->getPathWhereToStoreTheFile($url);
    }

    /**
     * @deprecated
     *
     * TODO Remove me; With UUID, I'm unique, I can be written!
     *
     * Decide if we are able to write to selected path
     *
     * @param string $url
     *
     * @return bool
     */
    public function canWriteFile($url)
    {
        return true;
//        $path = $this->getPathWhereToStoreTheFile($url);
//
//        return !is_file($path) && is_writable(dirname($path));
    }

    /**
     * @deprecated
     *
     * @param string $fileName
     *
     * @return string
     */
    public function assertGetStoragePathForFile(string $fileName)
    {
        $fileName = str_replace('/', '', $fileName);
        $fileName = str_replace('..', '', $fileName);
        $fileName = str_replace("\x0", '', $fileName);
        $fileName = trim($fileName);
        $fileName = addslashes($fileName);

        if (!is_file($this->storagePath . '/' . $fileName)) {
            throw new FileNotFoundException('File not found');
        }

        return $this->storagePath . '/' . $fileName;
    }

    /**
     * @deprecated
     * TODO: Move this method in FileRegistry?
     *
     * @param File $file
     *
     * @return string
     */
    public function getFileUrl(File $file): string
    {
        return $this->webUrl . $this->router->generate(
                'GET_public_download_imageName',
                [
                    'imageName' => $file->getUuid(),
                ]
            );
    }

    /**
     * TODO Choose adapter according to... some rules?
     *
     * @param array $rawData a $_FILES entry
     *
     * @return string
     */
    public function chooseAdapterFromUpload(array $rawData): string
    {
        return 'default';
    }

    /**
     * TODO Choose adapter according to... some rules?
     *
     * @param string $url
     *
     * @return string
     */
    public function chooseAdapterFromUrl(string $url): string
    {
        return 'default';
    }

    /**
     * @param string $adapter
     * @param array $rawData a $_FILES entry
     * @param bool $strictMode
     *
     * @return \Model\Entity\File
     *
     * @throws \Exception\Upload\UploadException
     * @throws \Exception\Flysystem\SystemNotFoundException
     */
    public function storeUploadedFile(
        string $adapter,
        array $rawData,
        bool $strictMode
    ): File {
        $uuid      = Uuid::uuid4();
        $tmpTarget = $this->tmpPath . '/' . $uuid->toString();

        $target = $this->generateRelativePath($uuid);

        if ($strictMode) {
            $success = move_uploaded_file($rawData['tmp_name'], $tmpTarget);
        } else {
            $success = rename($rawData['tmp_name'], $tmpTarget);
        }

        if (!$success) {
            throw new UploadException('Cannot save uploaded file');
        }

        $mimeType    = $this->getMimeType($tmpTarget);
        $contentHash = $this->getHashFile($tmpTarget);
        $fileSize    = filesize($tmpTarget);// maybe use SplFileInfo->getSize()?

        $stream = fopen($tmpTarget, 'r+');
        if (false === $stream) {
            throw new UploadException('Cannot save uploaded file', 500);
        }

        try {
            if (!$this->getFlysystemForConfigKey($adapter)->writeStream($target, $stream)) {
                throw new UploadException('Cannot save uploaded file');
            }
        }/* catch (SystemNotFoundException|FileExistsException $e) {// PHP 7.1 :(
            throw new UploadException('Cannot save uploaded file', 500, $e);
        }*/
        catch (SystemNotFoundException $e) {// PHP 7.1 :(
            throw new UploadException('Cannot save uploaded file', 500, $e);
        } catch (FileExistsException $e) {
            throw new UploadException('Cannot save uploaded file', 500, $e);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $originalName = new \SplFileInfo($rawData['name']);

        return (new File())
            ->setUuid($uuid->toString())
            ->setFileName($this->cleanFileName($originalName))
            ->setSize($fileSize)
            ->setAdapterName($adapter)
            ->setMimeType($mimeType)
            ->setContentHash($contentHash)
            ->setPath($target)
            ->setStatus(1);
    }

    /**
     * @TODO Manage errors
     *
     * @param string $adapter
     * @param string $url
     *
     * @return \Model\Entity\File
     *
     * @throws \Exception\Upload\UploadException
     * @throws \Exception\Flysystem\SystemNotFoundException
     */
    public function storeFileFromRemoteSource(
        string $adapter,
        string $url
    ): File {
        $uuid = Uuid::uuid4();

        $target       = $this->generateRelativePath($uuid);
        $originalName = new SplFileInfo($url);

        $file = (new File())
            ->setUuid($uuid->toString())
            ->setFileName($this->cleanFileName($originalName))
            ->setAdapterName($adapter)
            ->setPath($target);

        try {
            $downloadedFile = $this->fileDownloader->retrieveFileFromUrl($url);
        } catch (HttpDownloaderExceptionInterface $e) {
            throw new UploadException('Cannot save uploaded file', 500, $e);
        }

        $stream = StreamWrapper::getResource($downloadedFile->getStream());
        rewind($stream);

        // Can't use ETag if available since it's value is from a "black box":
        // https://en.wikipedia.org/wiki/HTTP_ETag
        // "An ETag is an opaque identifier assigned by a web server to a specific version of a resource found at a URL"
//        $contentHash = $this->getHashFile($tmpTarget);

        $ctx = hash_init($this->hashAlgo);
        hash_update_stream($ctx, $stream);
        $contentHash = hash_final($ctx);

        $file->setContentHash($contentHash)
            ->setSize($downloadedFile->getSize())
            ->setMimeType($downloadedFile->getMime());

        try {
            if (!$this->getFlysystemForConfigKey($adapter)->writeStream($target, $stream)) {
                throw new UploadException('Cannot save uploaded file');
            }
        }/* catch (SystemNotFoundException|FileExistsException $e) {// PHP 7.1 :(
            throw new UploadException('Cannot save uploaded file', 500, $e);
        }*/
        catch (SystemNotFoundException $e) {// PHP 7.1 :(
            throw new UploadException('Cannot save uploaded file', 500, $e);
        } catch (FileExistsException $e) {
            throw new UploadException('Cannot save uploaded file', 500, $e);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $file->setStatus(1);
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return false|resource
     *
     * @throws \Exception\Flysystem\FileNotFoundException
     * @throws \Exception\Flysystem\SystemNotFoundException
     */
    public function getFileStream(File $file)
    {
        try {
            return $this->getFlysystem($file)->readStream($file->getPath());
        } catch (FlysystemFileNotFoundException $e) {
            throw new FileNotFoundException(
                sprintf('File not found: %s', $file->getPublicId()),
                404,
                $e
            );
        }
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return bool
     */
    public function isStoredLocally(File $file)
    {
        try {
            $flysystem = $this->getFlysystem($file);

            // Method define with plugin \Flysystem\Plugins\IsLocal
            return $flysystem->isLocal();
        } catch (FlysystemFileNotFoundException $e) {
            throw new FileNotFoundException(
                sprintf('File not found: %s', $file->getPublicId()),
                404,
                $e
            );
        }
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return bool
     *
     * @throws \Exception\Flysystem\FileNotFoundException
     * @throws \Exception\Flysystem\SystemNotFoundException
     */
    public function deleteFile(File $file): bool
    {
        try {
            return $this->getFlysystem($file)->delete($file->getPath());
        } catch (FlysystemFileNotFoundException $e) {
            throw new FileNotFoundException(
                sprintf('File not found: %s', $file->getPublicId()),
                404,
                $e
            );
        }
    }

    /**
     * @param array $rawData
     *
     * @return bool
     */
    public function cleanTmpFile(array $rawData): bool
    {
        if (is_file($rawData['tmp_name'])) {
            return unlink($rawData['tmp_name']);
        }

        return false;
    }

    /**
     * @param string $path
     *
     * @return string|false
     */
    public function getMimeType(string $path): string
    {
        // TODO maybe replace me with hoa/mime?
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getHashFile(string $path): string
    {
        // TODO: Manage errors
        $ctx = hash_init($this->hashAlgo);

        hash_update_file($ctx, $path);

        return hash_final($ctx);
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getHashUrl(string $url): string
    {
        return hash($this->hashAlgo, $url);
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $uuid
     *
     * @return string
     */
    public function generateRelativePath(UuidInterface $uuid): string
    {
        return implode('/', str_split($uuid->getMostSignificantBitsHex(), 2))
            . '/' . $uuid->getLeastSignificantBitsHex();
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return string
     */
    public function getPath(File $file): string
    {
        $flysystem = $this->getFlysystem($file);

        if ($this->isStoredLocally($file)) {
            /* @var $adapter \League\Flysystem\Adapter\Local */
            $adapter = $flysystem->getAdapter();

            return $adapter->getPathPrefix() . $file->getPath();
        }

        return $file->getPath();
    }

    /**
     * /!\ This method might have cost, depending on Filesystem behind
     *
     * @param \Model\Entity\File $file
     *
     * @throws \Exception\Flysystem\FileNotFoundException
     */
    public function assertFileExist(File $file)
    {
        if (!$this->getFlysystem($file)->has($file->getPath())) {
            throw new FileNotFoundException($file->getPath());
        }
    }

    /**
     * @param \SplFileInfo $fileInfo
     *
     * @return string
     */
    protected function cleanFileName(SplFileInfo $fileInfo): string
    {
        $extension = '.' . $fileInfo->getExtension();

        return (string)Stringy::create($fileInfo->getBasename($extension))
            ->trim()
            ->collapseWhitespace()
            ->slugify(' ')
            ->append($extension);
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return \League\Flysystem\FilesystemInterface
     *
     * @throws \Exception\Flysystem\SystemNotFoundException
     */
    protected function getFlysystem(File $file): FilesystemInterface
    {
        return $this->getFlysystemForConfigKey($file->getAdapterName());
    }

    /**
     * @param string $configKey
     *
     * @return \League\Flysystem\FilesystemInterface
     *
     * @throws \Exception\Flysystem\SystemNotFoundException
     */
    protected function getFlysystemForConfigKey(string $configKey): FilesystemInterface
    {
        $flysystem = $this->flysystems[$configKey];

        if (!($flysystem instanceof FilesystemInterface)) {
            throw new SystemNotFoundException(sprintf(
                'System %s not found in config',
                $configKey
            ));
        }

        return $flysystem;
    }
}
