<?php declare(strict_types=1);

namespace Manager;

use League\Flysystem\FilesystemInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Stringy\StaticStringy;
use Stringy\Stringy;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Exception\ImageManager\DirectoryNotFoundException;
use Exception\Flysystem\SystemNotFoundException;
use Exception\Upload\UploadException;
use Model\Entity\File;

/**
 * Manages where to put a new file
 * and tells if putting a new file is possible
 * ===========================================
 *
 * @package Manager
 */
class StorageManager
{
    // TODO Remove me
    /** @var string $storagePath */
    private $storagePath;

    /**
     * @var string Where file are stored temporary, before being moved into final storage
     */
    private $tmpPath;

    /**
     * @var \Symfony\Component\Routing\Generator\UrlGenerator
     */
    private $router;

    /**
     * @var string
     */
    private $webUrl;

    /**
     * @var \League\Flysystem\FilesystemInterface[]
     */
    private $flysystems;

    /**
     * @var string prefered hash algo (highest sha* available)
     */
    private $hashAlgo;

    /**
     * StorageManager constructor.
     *
     * @param array $flysystems
     * @param string $tmpPath
     * @param string $storagePath
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $router
     * @param string $webUrl
     *
     * @throws \Exception\ImageManager\DirectoryNotFoundException
     */
    public function __construct(
        array $flysystems,
        string $tmpPath,
        string $storagePath,
        UrlGenerator $router,
        string $webUrl
    ) {
        $this->flysystems = $flysystems;
        $this->tmpPath    = realpath($tmpPath);

        // TODO Remove me
        $this->storagePath = realpath($storagePath);
        $this->router      = $router;

        $this->weburl = $webUrl;

        $this->hashAlgo = 'md5';// Old default one

        foreach (hash_algos() as $algo) {
            if (0 !== strpos($algo, 'sha')) {
                continue;
            }

            $this->hashAlgo = $algo;
        }

        // TODO Remove me
        if (!$this->storagePath) {
            throw new DirectoryNotFoundException('Storage path defined in "storage.path" configuration option does not exists');
        }

        if (false === $this->tmpPath) {
            throw new DirectoryNotFoundException('Temporary storage path defined in "storage.tmppath" configuration option does not exists');
        }
    }

    /**
     * Escape path, make sure it will not go out of the storagePath
     *
     * @param string $path
     *
     * @return string
     */
    public function escapeTargetPath(string $path): string
    {
        $fileName  = pathinfo($path, PATHINFO_BASENAME);
        $directory = realpath(dirname($path));

//        if ($directory !== realpath($this->storagePath)) {
//            return '';
//        }

        return $directory . '/' . $fileName;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function escapeName(string $fileName): string
    {
        $fileName = str_replace('..', '', $fileName);
        $fileName = str_replace('/', '-', $fileName);

        return $fileName;
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

        var_dump($url);

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


        return $this->weburl . $this->router->generate(
                'GET_public_download_imageName',
                [
                    'imageName' => $file->getUuid(),
                ]
            );

//        return $this->weburl . $this->router->generate(
//                'GET_public_download_imageName',
//                [
//                    'imageName' => $file->getFileName(),
//                ]
//            );
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getUrlByName(string $url): string
    {
        if (substr($url, 0, 1) === '/' && is_file($url)) {
            $path = realpath($url);
            $path = explode($this->storagePath, $path);

            return $this->weburl . $this->router->generate('GET_public_download_imageName', [
                    'imageName' => ltrim($path[1], '/ '),
                ]);
        }

        return $this->weburl . $this->router->generate('GET_public_download_imageName', [
                'imageName' => $this->getFileName($url),
            ]);
    }

    /**
     * @return string
     */
    public function getStoragePath()
    {
        return $this->storagePath;
    }

    /**
     * TODO Choose adapter according to... some rules?
     *
     * @param array $rawData a $_FILES entry
     *
     * @return string
     */
    public function chooseAdapter(array $rawData): string
    {
        return 'default';
    }

    /**
     * @param string $adapter
     * @param array $rawData a $_FILES entry
     * @param bool $strictMode
     *
     * @return File
     *
     * @throws \Exception\Upload\UploadException
     */
    public function storeUploadedFile(
        string $adapter,
        array $rawData,
        bool $strictMode
    ): File {

        $mimeType    = '';
        $contentHash = '';
        $uuid        = Uuid::uuid4();
        $tmpTarget   = $this->tmpPath . '/' . $uuid->toString();
        $fileSize    = 0;

        $target = $this->generatePath($uuid);

        if (false === $strictMode) {
            $success = rename($rawData['tmp_name'], $tmpTarget);
        } else {
            $success = move_uploaded_file($rawData['tmp_name'], $tmpTarget);
        }

        if ($success) {
            $mimeType    = $this->getMimeType($tmpTarget);
            $contentHash = $this->getHashFile($tmpTarget);
            $fileSize    = filesize($tmpTarget);// maybe use SplFileInfo->getSize()?

            $stream = fopen($tmpTarget, 'r+');
            // TODO use dynamic key
            $success = $this->flysystems[$adapter]->writeStream($target, $stream);
            fclose($stream);
        }

        if (!$success) {
            throw new UploadException('Cannot save uploaded file');
        }

        return (new File())
            ->setUuid($uuid->toString())
            ->setFileName(Stringy::create($rawData['name'])
                ->trim()
                ->collapseWhitespace()
                ->slugify(' ')
            )
            ->setSize($fileSize)
            ->setAdapterName($adapter)
            ->setMimeType($mimeType)
            ->setContentHash($contentHash)
            ->setPath($target);
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return bool
     */
    public function deleteFile(File $file): bool
    {
        $flysystem = $this->flysystems[$file->getAdapterName()];

        if (!($flysystem instanceof FilesystemInterface)) {
            throw new SystemNotFoundException(sprintf(
                'System %s not found in config',
                $file->getAdapterName()
            ));
        }

        return $flysystem->delete($file->getPath());
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
        return hash_file($this->hashAlgo, $path);
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $uuid
     *
     * @return string
     */
    public function generatePath(UuidInterface $uuid): string
    {
        return implode('/', str_split($uuid->getMostSignificantBitsHex(), 2))
            . '/' . $uuid->getLeastSignificantBitsHex();
    }
}
