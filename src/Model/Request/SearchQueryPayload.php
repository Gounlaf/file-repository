<?php declare(strict_types=1);

namespace Model\Request;

/**
 * @package Model\Request
 */
class SearchQueryPayload
{
    /** @var array $tags */
    private $tags = [];

    /** @var string $searchQuery */
    private $searchQuery = '';

    /** @var int $limit */
    private $limit = 50;

    /** @var int $offset */
    private $offset = 0;

    /**
     * @param array $tags
     *
     * @return SearchQueryPayload
     */
    public function setTags(array $tags = [])
    {
        $this->tags = array_filter((array)$tags);

        return $this;
    }

    /**
     * @param string $searchQuery
     *
     * @return SearchQueryPayload
     */
    public function setSearchQuery(string $searchQuery)
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return SearchQueryPayload
     */
    public function setLimit(int $limit)
    {
        if ($limit > 100 || $limit < 0) {
            $limit = 100;
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return SearchQueryPayload
     */
    public function setOffset(int $offset)
    {
        if ($offset < 0) {
            $offset = 0;
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return bool
     */
    public function hasTags(): bool
    {
        return !empty($this->tags);
    }

    /**
     * @return string
     */
    public function getSearchQuery(): string
    {
        return $this->searchQuery;
    }

    /**
     * @return bool
     */
    public function hasSearchQuery(): bool
    {
        return !empty($this->searchQuery);
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return bool
     */
    public function hasLimit(): bool
    {
        return $this->limit > 0;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return bool
     */
    public function hasOffset(): bool
    {
        return $this->limit > 0;
    }
}
