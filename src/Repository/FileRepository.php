<?php declare(strict_types=1);

namespace Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Manager\StorageManager;
use Model\Entity\File;
use Model\Request\SearchQueryPayload;
use Repository\Domain\FileRepositoryInterface;

/**
 * @package Repository\Domain
 */
class FileRepository implements FileRepositoryInterface
{
    /**
     * @var StorageManager $storageManager
     */
    private $storageManager;

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @param StorageManager $manager
     * @param EntityManager $em
     */
    public function __construct(StorageManager $manager, EntityManager $em)
    {
        $this->storageManager = $manager;
        $this->em             = $em;
    }

    /**
     * @inheritDoc
     */
    public function findBySearchQuery(SearchQueryPayload $searchQuery): Paginator
    {
        $qb = $this->repository()
            ->createQueryBuilder('f')
            ->innerJoin('f.tags', 't');

        $criteria = Criteria::create();

        if ($searchQuery->hasTags()) {
            $criteria->andWhere(Criteria::expr()->in('t.name', $searchQuery->getTags()));
        }

        if ($searchQuery->hasSearchQuery()) {
            $criteria->andWhere(Criteria::expr()->contains('f.fileName', $searchQuery->getSearchQuery()));
        }

        if ($searchQuery->hasLimit()) {
            $criteria->setMaxResults($searchQuery->getLimit());

            if ($searchQuery->hasOffset()) {
                $criteria->setFirstResult($searchQuery->getOffset() * $searchQuery->getLimit());
            }
        }

        return new Paginator($qb->addCriteria($criteria
            ->orderBy(array(
                'f.dateAdded' => Criteria::DESC,
                'f.fileName'  => Criteria::ASC
            ))
        ));
    }

    /**
     * @inheritDoc
     */
    public function findFileByContentHash(string $hash): Collection
    {
        return $this->repository()->matching(Criteria::create()
            ->where(Criteria::expr()->eq('contentHash', $hash))
        );
    }

    /**
     * @inheritDoc
     */
    public function getFileByContentHash(string $hash): Collection
    {
        $collection = $this->findFileByContentHash($hash);

        if (0 == $collection->count()) {
            throw new EntityNotFoundException();
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function findFileByName(string $name): Collection
    {
        return $this->repository()->matching(Criteria::create()
            ->where(Criteria::expr()->eq('fileName', $name))
        );
    }

    /**
     * @inheritDoc
     */
    public function getFileByName(string $name): Collection
    {
        $collection = $this->findFileByName($name);

        if (0 == $collection->count()) {
            throw new EntityNotFoundException();
        }

        return $collection;
    }


    /**
     * @inheritDoc
     */
    public function findFileByPublicId(string $publicId): File
    {
        $collection = $this->repository()->matching(Criteria::create()
            ->where(Criteria::expr()->eq('publicId', $publicId))
        );

        $file = $collection->first();

        if (empty($file)) {
            return null;
        }

        return $file;
    }

    /**
     * @inheritDoc
     */
    public function getFileByPublicId(string $publicId): File
    {
        $collection = $this->repository()->matching(Criteria::create()
            ->where(Criteria::expr()->eq('publicId', $publicId))
        );

        if (0 == $collection->count()) {
            throw new EntityNotFoundException();
        }

        if (1 < $collection->count()) {
            // TODO Throw a more specific exception
            // Something when wrong; public id are meant to be unique
            throw new \LogicException();
        }

        return $collection->first();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return (int)$this->repository()->matching(Criteria::create())->count();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function repository(): EntityRepository
    {
        return $this->em->getRepository(File::class);
    }
}
