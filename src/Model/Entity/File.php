<?php

namespace Model\Entity;

use \DateTime;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Represents a file from the registry
 *
 * @package Model\Entity
 */
class File
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Might replace $id
     *
     * @var string|\Ramsey\Uuid\UuidInterface
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $publicId;

    /**
     * @var string
     */
    protected $adapterName;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $contentHash;

    /**
     * @var \DateTime
     */
    protected $dateAdded;

    /**
     * @var string
     */
    protected $mimeType;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var \Model\Entity\Tag[]|\Doctrine\Common\Collections\ArrayCollection
     */
    protected $tags;

    public function __construct()
    {
        $this->dateAdded = new DateTime();
        $this->tags      = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return File
     */
    public function setId($id): File
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \Ramsey\Uuid\UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return Uuid::fromString($this->uuid);
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface|string $uuid
     *
     * @return File
     */
    public function setUuid($uuid): File
    {
        $this->uuid = $uuid instanceof UuidInterface ? $uuid->toString() : $uuid;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicId(): string
    {
        return $this->publicId;
    }

    /**
     * @param string $publicId
     *
     * @return File
     */
    public function setPublicId(string $publicId): File
    {
        $this->publicId = $publicId;

        return $this;
    }


    /**
     * @return string
     */
    public function getAdapterName(): string
    {
        return $this->adapterName;
    }

    /**
     * @param string $adapterName
     *
     * @return File
     */
    public function setAdapterName(string $adapterName): File
    {
        $this->adapterName = $adapterName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return File
     */
    public function setPath(string $path): File
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     *
     * @return File
     */
    public function setFileName($fileName): File
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentHash()
    {
        return $this->contentHash;
    }

    /**
     * @param string $contentHash
     *
     * @return File
     */
    public function setContentHash($contentHash): File
    {
        $this->contentHash = $contentHash;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param string|\DateTime $dateAdded
     *
     * @return File
     */
    public function setDateAdded($dateAdded): File
    {
        $this->dateAdded = $dateAdded instanceof DateTime ? $dateAdded : new DateTime($dateAdded);

        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     *
     * @return File
     */
    public function setMimeType($mimeType): File
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     *
     * @return File
     */
    public function setSize(int $size): File
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return File
     */
    public function addTag(Tag $tag): File
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addFile($this);
        }

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return File
     */
    public function deleteTag(Tag $tag): File
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
            $tag->getFiles()->removeElement($this);
        }

        return $this;
    }

    /**
     * @return PersistentCollection|ArrayCollection|Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }
}
