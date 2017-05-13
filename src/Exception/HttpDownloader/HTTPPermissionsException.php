<?php

namespace Exception\HttpDownloader;

class HTTPPermissionsException extends \Exception implements HttpDownloaderExceptionInterface
{
    public function __construct(\Exception $previous = null)
    {
        \Exception::__construct('Access to the HTTP resource is forbidden, cannot download the image', 0, $previous);
    }
}
