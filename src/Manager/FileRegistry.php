<?php declare(strict_types=1);

namespace Manager;

use \DateTime;
use \RuntimeException;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Stringy\Stringy;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Exception\Flysystem\SystemNotFoundException;
use Exception\Upload\DuplicatedContentException;
use Model\Entity\File;
use Model\Request\SearchQueryPayload;
use Repository\Domain\FileRepositoryInterface;

/**
 * @package Manager
 */
class FileRegistry
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Symfony\Component\Routing\Generator\UrlGenerator
     */
    private $router;

    /**
     * @var \Manager\StorageManager
     */
    private $storageManager;

    /**
     * @var \Repository\Domain\FileRepositoryInterface
     */
    private $repository;

    /**
     * @var bool
     */
    private $duplicateAllowed;

    /**
     * @var string
     */
    private $webUrl;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $router
     * @param \Manager\StorageManager $manager
     * @param \Repository\Domain\FileRepositoryInterface $repository
     * @param bool $allowDuplicate
     * @param string $webUrl
     */
    public function __construct(
        EntityManager $em,
        UrlGenerator $router,
        StorageManager $manager,
        FileRepositoryInterface $repository,
        bool $allowDuplicate,
        string $webUrl
    ) {
        $this->em               = $em;
        $this->router           = $router;
        $this->storageManager   = $manager;
        $this->repository       = $repository;
        $this->duplicateAllowed = $allowDuplicate;
        $this->webUrl           = $webUrl;
    }

    /**
     * @param string $fileName File name or URL address
     *
     * @return bool
     */
    public function existsInRegistry($fileName): bool
    {
        return !$this->repository->findFileByName($fileName)->isEmpty();
    }

    /**
     * @param string $hash File's content hash
     *
     * @return bool
     */
    public function existsInRegistryByHash($hash): bool
    {
        return !$this->repository->findFileByContentHash($hash)->isEmpty();
    }

    /**
     * TODO Throw another "FileNotFoundException" (from a different/more appropriate namespace)
     *
     * @param string $hash
     *
     * @return \Model\Entity\File[]|\Doctrine\Common\Collections\Collection
     *
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function getFileByContentHash(string $hash): Collection
    {
        try {
            return $this->repository->getFileByContentHash($hash);
        } catch (EntityNotFoundException $e) {
            throw new FileNotFoundException(null, 404, $e, $hash);
        }
    }

    /**
     * TODO Throw another "FileNotFoundException" (from a different/more appropriate namespace)
     *
     * @param string $name
     *
     * @return \Model\Entity\File[]|\Doctrine\Common\Collections\Collection
     *
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function getFileByName(string $name): Collection
    {
        try {
            return $this->repository->getFileByName($name);
        } catch (EntityNotFoundException $e) {
            throw new FileNotFoundException(null, 404, $e, $name);
        }
    }

    /**
     * TODO Throw another "FileNotFoundException" (from a different/more appropriate namespace)
     *
     * @param string $publicId
     *
     * @return \Model\Entity\File
     *
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function getFileByPublicId(string $publicId): File
    {
        try {
            return $this->repository->getFileByPublicId($publicId);
        } catch (EntityNotFoundException $e) {
            throw new FileNotFoundException(null, 404, $e, $publicId);
        }
    }

    /**
     * @param \Model\Request\SearchQueryPayload $searchQuery
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findBySearchQuery(SearchQueryPayload $searchQuery): Paginator
    {
        return $this->repository->findBySearchQuery($searchQuery);
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return string
     */
    public function getFileUrl(File $file): string
    {
        return $this->webUrl . $this->router->generate(
                'GET_public_download_imageName',
                [
                    'imageName' => $file->getPublicId(),
                ]
            );
    }

    /**
     * @param \Model\Entity\File $file
     *
     * @return string
     */
    public function generatePublicId(File $file): string
    {
        $info = new \SplFileInfo($file->getFileName());
        $ext = $info->getExtension();

        if (!empty($ext)) {
            $ext = '.' . $ext;
        }

        return (string) Stringy::create($info->getBasename($ext))
            ->slugify()
            ->append($ext)
            ->toLowerCase()
            ->prepend('-')
            ->prepend($file->getUuid()->getMostSignificantBitsHex());
    }

    /**
     * @deprecated
     *
     * In case of a upload failure
     * allow to delete saved file from the disk
     * (should not be used in other cases)
     *
     * @param string $path
     */
    public function revertUploadedDuplicate(string $path)
    {
        $this->storageManager->cleanTmpFile(array(
            'tmp_name' => $path
        ));
    }

    /**
     * @deprecated
     *
     * Put a file into the registry
     * after successful save/upload to disk
     *
     * @param string $fileName
     * @param string $mimeType
     *
     * @throws FileNotFoundException
     * @throws DuplicatedContentException
     *
     * @return File
     */
    public function registerByName(string $fileName, string $mimeType)
    {
        $filePath = $this->storageManager->getPathWhereToStoreTheFile($fileName);
        $fileName = $this->storageManager->getFileName($fileName);

        if (!is_file($filePath)) {
            throw new FileNotFoundException($filePath);
        }

        $hash      = hash_file('md5', $filePath);
        $duplicate = $this->repository->getFileByContentHash($hash);

        if ($duplicate instanceof File) {
            throw new DuplicatedContentException(
                'There already exists a file with the same content. ' .
                'In this case please abort upload action and remove the file',
                $duplicate
            );
        }

        $file = new File();
        $file->setFileName($fileName);
        $file->setContentHash($hash);
        $file->setDateAdded(new \DateTime());
        $file->setMimeType($mimeType);

        // persist and flush changes
        $this->save($file);

        return $file;
    }

    /**
     * Put a file into the registry
     *
     * @param \Model\Entity\File $file
     *
     * @return \Model\Entity\File
     */
    public function register(File $file): File
    {
        $file->setDateAdded(new DateTime());
        $file->setPublicId($this->generatePublicId($file));

        // persist and flush changes
        $this->save($file);

        return $file;
    }


    /**
     * @param \Model\Entity\File $file
     *
     * @return \Model\Entity\File
     */
    public function save(File $file): File
    {
        $this->em->persist($file);
        $this->em->flush($file);

        return $file;
    }

    /**
     * TODO Throw a more specific exception
     *
     * Delete a file from disk and from the registry
     *
     * @param \Model\Entity\File $file
     *
     * @throws \RuntimeException
     */
    public function deleteFile(File $file)
    {
        try {
            if (false === $this->storageManager->deleteFile($file)) {
                throw new RuntimeException(sprintf(
                    'Something when wrong during file deletion; adapter: %s; path: %s',
                    $file->getAdapterName(),
                    $file->getPath()
                ));
            }
        } catch (SystemNotFoundException $e) {
            throw new RuntimeException(sprintf(
                'Something when wrong during file deletion; adapter: %s; path: %s',
                $file->getAdapterName(),
                $file->getPath()
            ), 0, $e);
        }

        $this->em->remove($file);
        $this->em->flush();
    }

    /**
     * @return bool
     */
    public function isDuplicateAllowed(): bool
    {
        return $this->duplicateAllowed;
    }
}
