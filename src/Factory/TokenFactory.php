<?php declare(strict_types=1);

namespace Factory;

use \DateTime;

use Model\Entity\Token;
use Ramsey\Uuid\Uuid;

/**
 * @package Factory\TokenFactory
 */
class TokenFactory
{
    /**
     * @param array $roles
     * @param \DateTime $expires
     * @param array $data
     *
     * @return \Model\Entity\Token
     */
    public function createNewToken(array $roles, DateTime $expires, array $data = []): Token
    {
        return (new Token())
            ->setUuid(Uuid::uuid4())
            ->setRoles($roles)
            ->setExpirationDate($expires)
            ->setData($data);
    }
}
