<?php declare(strict_types=1);

namespace Service;

use Exception\HttpDownloader\HttpException;
use \finfo;

use Exception\HttpDownloader\FileSizeLimitExceededException;
use Exception\HttpDownloader\HTTPPermissionsException;
use Exception\HttpDownloader\InvalidFileTypeException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Debug\Exception\ContextErrorException;

use Model\SavedFile;

/**
 * HTTP Client for files downloading
 *
 * @package Service
 */
class HttpFileDownloader
{
    /**
     * @var int
     */
    protected $maxFileSizeLimit = (1024 * 1024 * 1024); // megabyte

    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var resource
     */
    protected $_stream;

    /**
     * @var string[]
     */
    protected $allowedMimes = [];

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * HttpFileDownloader constructor.
     *
     * @param \GuzzleHttp\Client $client
     * @param array $allowedMimes
     * @param int $sizeLimit
     */
    public function __construct(Client $client, array $allowedMimes, int $sizeLimit)
    {
        $this->allowedMimes     = $allowedMimes;
        $this->maxFileSizeLimit = $sizeLimit;

        $this->client = $client;
    }

    /**
     * @param string $url
     *
     * @return \Model\SavedFile
     *
     * @throws \Exception\HttpDownloader\FileSizeLimitExceededException
     * @throws \Exception\HttpDownloader\HttpException
     */
    public function retrieveFileFromUrl(string $url)
    {
        $headResponse = $this->client->head($url);

        $this->assertResponse($headResponse);

        // Server sent Content-Length; we can check file size now
        if ($headResponse->hasHeader('Content-Length')) {
            $this->assertSize((int)$headResponse->getHeaderLine('Content-Length'));
        }

        if ($headResponse->hasHeader('Content-Type')) {
            $this->assertMimeType($headResponse->getHeaderLine('Content-Type'));
        }

        $getResponse = $this->client->get($url, [
            'allow_redirects' => true,
            'sink'            => tmpfile()
        ]);

        $this->assertResponse($headResponse);

        // Size & mime are checked again with actual download file

        $body = $getResponse->getBody();
        $size = $body->getSize();

        $this->assertSize($body->getSize());

        $resource = StreamWrapper::getResource($body);
        fseek($resource, 0);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer(fread($resource, 1024));
        fclose($resource);

        $this->assertMimeType($mime);

        return new SavedFile($body, $mime, $size);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @throws \Exception\HttpDownloader\HttpException
     */
    protected function assertResponse(ResponseInterface $response)
    {
        if (200 != $response->getStatusCode()) {
            throw new HttpException(sprintf(
                'Got http status: %d',
                $response->getStatusCode()
            ));
        }
    }

    /**
     * @param int $fileSize
     *
     * @throws \Exception\HttpDownloader\FileSizeLimitExceededException
     */
    protected function assertSize(int $fileSize)
    {
        $allowed = $this->getMaxFileSizeLimit();

        if ($fileSize > $allowed) {
            throw new FileSizeLimitExceededException($allowed);
        }
    }

    /**
     * @param string $mimeType
     *
     * @throws \Exception\HttpDownloader\InvalidFileTypeException
     */
    protected function assertMimeType(string $mimeType)
    {
        $parts = explode(';', $mimeType);

        $allowed = $this->getAllowedMimes();
        if (!in_array(current($parts), $allowed)) {
            throw new InvalidFileTypeException(current($parts), $allowed);
        }
    }

    /**
     * @return int
     */
    public function getMaxFileSizeLimit(): int
    {
        return $this->maxFileSizeLimit;
    }

    /**
     * @param int $maxFileSizeLimit
     *
     * @return \Service\HttpFileDownloader
     */
    public function setMaxFileSizeLimit(int $maxFileSizeLimit): HttpFileDownloader
    {
        $this->maxFileSizeLimit = $maxFileSizeLimit;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAllowedMimes(): array
    {
        return $this->allowedMimes;
    }

    /**
     * @param \string[] $allowedMimes
     *
     * @return \Service\HttpFileDownloader
     */
    public function setAllowedMimes(array $allowedMimes): HttpFileDownloader
    {
        $this->allowedMimes = $allowedMimes;

        return $this;
    }
}
