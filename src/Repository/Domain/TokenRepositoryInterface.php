<?php declare(strict_types=1);

namespace Repository\Domain;

use Doctrine\Common\Collections\Collection;
use Model\Entity\Token;

/**
 * @package Repository\Domain
 */
interface TokenRepositoryInterface
{

    /**
     * @param string $uuid
     *
     * @return \Model\Entity\Token|null
     */
    public function findTokenByUuid(string $uuid);

    /**
     * @param string $uuid
     *
     * @return \Model\Entity\Token
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function getTokenByUuid(string $uuid): Token;

    /**
     * @return \Model\Entity\Token[]|\Doctrine\Common\Collections\Collection
     */
    public function findExpiredTokens(): Collection;
}
