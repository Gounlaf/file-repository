<?php

namespace Actions\Upload;

use Exception\Upload\DuplicatedContentException;
use Exception\Upload\InvalidUrlException;
use Model\Entity\File;

/**
 * @package Actions\Upload
 */
class AddByUrlActionHandler extends AbstractUploadActionHandler
{
    /**
     * @param string $fileUrl
     * @param array $tags
     *
     * @return \Actions\Upload\AddByUrlActionHandler
     */
    public function setData(string $fileUrl, array $tags = []): AddByUrlActionHandler
    {
        parent::setData($fileUrl, $tags);

        return $this;
    }

    /**
     * @throws InvalidUrlException
     * @return array
     */
    public function execute(): array
    {
        $this->handleValidation();

        $code = 418;
        $file = null;

        try {
            $file = $this->handleDownload($this->source);
            $code = 200;
        } catch (DuplicatedContentException $e) {
            $file = $e->getDuplicate();
            $code = 301;
        };

        return [
            'success' => true,
            'status'  => 'OK',
            'code'    => $code,
            'url'     => $this->registry->getFileUrl($file)
        ];
    }

    /**
     * @param string $url
     *
     * @return \Model\Entity\File
     *
     * @throws \Exception\Upload\UploadException
     * @throws \Exception\Flysystem\SystemNotFoundException
     */
    protected function handleDownload(string $url): File
    {
        if (!$this->registry->isDuplicateAllowed()) {
            // "Low verification": rely on file content
            $hash = $this->manager->getHashUrl($url);

            if ($this->registry->existsInRegistryByHash($hash)) {
                throw new DuplicatedContentException(
                    'Duplicate content',
                    $this->registry->getFileByContentHash($hash)
                );
            }
        }

        $adapter = $this->manager->chooseAdapterFromUrl($url);

        $uploadedFile = $this->manager->storeFileFromRemoteSource(
            $adapter,
            $url
        );

        $file = $this->registry->register($uploadedFile);

        foreach ($this->tags as $tag) {
            $this->tagManager->attachTagToFile($tag, $file);
        }

        return $file;
    }

    /**
     * @inheritDoc
     *
     * @throws \Exception\Upload\InvalidUrlException
     */
    protected function handleValidation(): array
    {
        $this->assertValidUrl($this->source);

        return array();
    }

    /**
     * @param string $url
     *
     * @throws \Exception\Upload\InvalidUrlException
     *
     * @return bool
     */
    public function assertValidUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException();
        }

        if (!$this->getController()->supportsProtocol(parse_url($url, PHP_URL_SCHEME))) {
            throw new InvalidUrlException(InvalidUrlException::INVALID_SCHEMA);
        }
    }
}
