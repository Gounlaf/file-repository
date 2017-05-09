<?php declare(strict_types=1);

namespace Repository\Domain;

use Model\Entity\File;

/**
 * @package Repository\Domain
 */
interface FileRepositoryInterface
{
    /**
     * @param string $name File name or URL address
     *
     * @return File|null
     */
    public function fetchOneByName(string $name): File;

    /**
     * @param array $tags
     * @param string $searchQuery
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function findByQuery(array $tags, string $searchQuery = '', int $limit, int $offset): array;

    /**
     * @param string $hash
     *
     * @return \Model\Entity\File|null
     */
    public function getFileByContentHash(string $hash): File;

    /**
     * Retrieve a File entity by it's public id. File must exist, otherwise an
     * {@Doctrine\ORM\EntityNotFoundException} is thrown
     *
     * @param string $publicId
     *
     * @return \Model\Entity\File
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function getFileByPublicId(string $publicId): File;
}
