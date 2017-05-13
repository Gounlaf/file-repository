<?php

namespace Exception\HttpDownloader;

class InvalidFileTypeException extends \Exception implements HttpDownloaderExceptionInterface
{
    public function __construct(string $mime, array $allowedMimes, \Exception $previous = null)
    {
        parent::__construct('Stream is of unsupported mime type "' . $mime . '", allowed mimes: ' . implode(', ', $allowedMimes), 1, $previous);
    }
}
