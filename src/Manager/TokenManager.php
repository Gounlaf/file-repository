<?php declare(strict_types=1);

namespace Manager;

use \DateTime;

use Doctrine\ORM\EntityManager;

use Factory\TokenFactory;
use Manager\Domain\TokenManagerInterface;
use Model\Entity\Token;
use Repository\Domain\TokenRepositoryInterface;

/**
 * @package Manager
 */
class TokenManager implements TokenManagerInterface
{
    /**
     * @var \Repository\Domain\TokenRepositoryInterface
     */
    private $repository;

    /**
     * @var string
     */
    private $adminToken;

    /**
     * @var \Factory\TokenFactory
     */
    private $factory;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @param \Repository\Domain\TokenRepositoryInterface $repository
     * @param \Factory\TokenFactory $factory
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param string $adminToken
     */
    public function __construct(
        TokenRepositoryInterface $repository,
        TokenFactory $factory,
        EntityManager $entityManager,
        string $adminToken
    ) {
        $this->repository = $repository;
        $this->factory    = $factory;
        $this->em         = $entityManager;
        $this->adminToken = $adminToken;
    }

    /**
     * @inheritdoc
     */
    public function isTokenValid(string $tokenStr, array $requiredRoles = []): bool
    {
        if ($this->isAdminToken($tokenStr)) {
            return true;
        }

        if (empty($requiredRoles)) {
            return false;
        }

        $token = $this->repository->findTokenByUuid($tokenStr);
        if (empty($token) || !$token->isNotExpired()) {
            return false;
        }

        // at least one role must match
        foreach ($requiredRoles as $roleName) {
            if ($token->hasRole($roleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isAdminToken(string $tokenId): bool
    {
        return $this->getAdminToken() === $tokenId;
    }

    /**
     * @inheritdoc
     */
    public function generateNewToken(array $roles, DateTime $expires, array $data = []): Token
    {
        $token = $this->factory->createNewToken($roles, $expires, $data);

        $this->em->persist($token);
        $this->em->flush($token);

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function removeToken(Token $token)
    {
        $this->em->remove($token);
        $this->em->flush($token);
    }

    /**
     * @return string
     */
    public function getAdminToken(): string
    {
        return $this->adminToken;
    }
}
