<?php declare(strict_types=1);

namespace Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Manager\StorageManager;
use Model\Entity\File;
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
     * @param EntityManager  $em
     */
    public function __construct(StorageManager $manager, EntityManager $em)
    {
        $this->storageManager = $manager;
        $this->em             = $em;
    }

    /**
     * @inheritDoc
     */
    public function findByQuery(array $tags, string $searchQuery = '', int $limit = 50, int $offset = 0): array
    {
        $qb = $this->em->getRepository(File::class)
            ->createQueryBuilder('f');
        $qb->select();
        $qb->innerJoin('f.tags', 't');

        if (count($tags) > 0) {
            $qb->andWhere('t.name in (:tags)')
                ->setParameter('tags', $tags);
        }

        if (strlen($searchQuery) > 0) {
            $qb->andWhere('f.fileName LIKE :searchQuery')
                ->setParameter('searchQuery', '%' . $searchQuery . '%');
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }

        // max results counting
        $countingQuery = clone $qb;
        $countingQuery->select('count(f)');

        // order by
        $qb->addOrderBy('f.dateAdded', 'DESC');
        $qb->addOrderBy('f.fileName', 'ASC');

        return [
            'results' => $qb->getQuery()->getResult(),
            'max'     => $countingQuery->getQuery()->getScalarResult()[0][1] ?? 0,
        ];
    }

    /**
     * @inheritDoc
     */
    public function findFileByContentHash(string $hash): Collection
    {
        return $this->em->getRepository(File::class)
            ->matching(Criteria::create()
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
        return $this->em->getRepository(File::class)
            ->matching(Criteria::create()
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
        $collection = $this->em->getRepository(File::class)
            ->matching(Criteria::create()
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
        $collection = $this->em->getRepository(File::class)
            ->matching(Criteria::create()
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
}
