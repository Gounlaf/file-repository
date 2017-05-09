<?php declare(strict_types=1);

namespace Repository;

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
    public function fetchOneByName(string $name): File
    {
        $name = $this->storageManager->getStorageFileName($name);

        return $this->em->getRepository(File::class)
            ->findOneBy(['fileName' => $name]);
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
    public function getFileByContentHash(string $hash): File
    {
        return $this->em->getRepository(File::class)
            ->findOneBy(['contentHash' => $hash]);
    }

    /**
     * @inheritDoc
     */
    public function getFileByPublicId(string $publicId): File
    {
        /** @var $file \Model\Entity\File */
        $file = $this->em->getRepository(File::class)
            ->findOneBy(['publicId' => $publicId]);

        if (null === $file) {
            throw new EntityNotFoundException();
        }

        return $file;
    }
}
