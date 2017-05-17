<?php declare(strict_types=1);

namespace Model\Entity;

/**
 * @package Model\Entity
 */
class AdminToken extends Token
{
    /**
     * @var string
     */
    protected $customId;

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setCustomId(string $id)
    {
        $this->customId = $id;

        return $this;
    }
}
