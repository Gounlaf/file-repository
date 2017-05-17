<?php declare(strict_types=1);

namespace Repository\Domain;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Model\Entity\File;
use Model\Request\SearchQueryPayload;

/**
 * @package Repository\Domain
 */
interface FileRepositoryInterface
{
    /**
     * @param \Model\Request\SearchQueryPayload $searchQuery
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findBySearchQuery(SearchQueryPayload $searchQuery): Paginator;

    /**
     * @param string $name
     *
     * @return \Model\Entity\File[]|\Doctrine\Common\Collections\Collection
     */
    public function findFileByName(string $name): Collection;

    /**
     * File must exist, otherwise an {@Doctrine\ORM\EntityNotFoundException} is thrown
     *
     * @param string $name
     *
     * @return \Model\Entity\File[]|\Doctrine\Common\Collections\Collection
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function getFileByName(string $name): Collection;

    /**
     * @param string $hash
     *
     * @return \Model\Entity\File[]|\Doctrine\Common\Collections\Collection
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function findFileByContentHash(string $hash): Collection;

    /**
     * File must exist, otherwise an {@Doctrine\ORM\EntityNotFoundException} is thrown
     *
     * @param string $hash
     *
     * @return \Model\Entity\File[]|\Doctrine\Common\Collections\Collection
     */
    public function getFileByContentHash(string $hash): Collection;


    /**
     * Retrieve a File entity by it's public id.
     *
     * @param string $publicId
     *
     * @return \Model\Entity\File|null
     */
    public function findFileByPublicId(string $publicId);

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

    /**
     * @return mixed
     */
    public function count(): int;
}
