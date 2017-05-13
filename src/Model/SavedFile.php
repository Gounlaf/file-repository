<?php

namespace Model;

use Psr\Http\Message\StreamInterface;

/**
 * Stores information about just saved/uploaded file
 * the data is immutable
 *
 * @package Model
 */
class SavedFile
{
    /**
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $stream;

    /**
     * @var string
     */
    protected $mime;

    /**
     * @var int
     */
    protected $size;

    public function __construct(
        StreamInterface $stream,
        string $mime,
        int $size
    ) {
        $this->stream = $stream;
        $this->mime   = $mime;
        $this->size   = $size;
    }

    /**
     * @return \Psr\Http\Message\StreamInterface
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
}
