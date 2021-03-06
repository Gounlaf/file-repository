<?php declare(strict_types=1);

namespace Model\Request;

/**
 * @package Model\Request
 */
class AddByUrlPayload
{
    /**
     * @var string $fileUrl
     */
    private $fileUrl = '';

    /**
     * @var array $tags
     */
    private $tags = [];

    /**
     * @param string $fileUrl
     * @return AddByUrlPayload
     */
    public function setFileUrl(string $fileUrl)
    {
        $this->fileUrl = $fileUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileUrl(): string
    {
        return $this->fileUrl;
    }

    /**
     * @param array $tags
     * @return AddByUrlPayload
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

}