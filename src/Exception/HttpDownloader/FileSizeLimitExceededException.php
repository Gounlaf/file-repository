<?php

namespace Exception\HttpDownloader;

use \Throwable;
use \RuntimeException;

class FileSizeLimitExceededException extends RuntimeException implements HttpDownloaderExceptionInterface
{
    public function __construct(int $bytes, Throwable $previous = null)
    {
        parent::__construct('Max file size of ' . $bytes . ' bytes exceeded', 2, $previous);
    }
}
