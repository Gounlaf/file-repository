<?php declare(strict_types=1);

namespace Repository;

use \DateTime;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Model\Entity\Token;
use Repository\Domain\TokenRepositoryInterface;

/**
 * @package Repository
 */
class TokenRepository implements TokenRepositoryInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public function findTokenByUuid(string $uuid): Token
    {
        return $this->repository()->matching(Criteria::create()
            ->where(Criteria::expr()->eq('uuid', $uuid))
        )->first();
    }

    /**
     * @inheritDoc
     */
    public function getTokenByUuid(string $uuid): Token
    {
        $token = $this->findTokenByUuid($uuid);
        if (empty($token)) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Token::class, [$uuid]);
        }

        return $token;
    }

    /**
     * @inheritDoc
     */
    public function findExpiredTokens(): Collection
    {
        return $this->repository()->matching(Criteria::create()
            ->where(Criteria::expr()->lte('expirationDate', new DateTime()))
        );
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function repository(): EntityRepository
    {
        return $this->em->getRepository(Token::class);
    }
}
