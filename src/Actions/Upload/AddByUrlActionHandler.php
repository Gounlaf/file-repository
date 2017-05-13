<?php

namespace Actions\Upload;

use Exception\Upload\UploadException;
use GuzzleHttp\Client;

use Exception\Upload\DuplicatedContentException;
use Exception\Upload\InvalidUrlException;
use Manager\Domain\TagManagerInterface;
use Manager\FileRegistry;
use Manager\StorageManager;
use Model\Entity\File;

/**
 * @package Actions\Upload
 */
class AddByUrlActionHandler extends AbstractUploadActionHandler
{
    /**
     * AddByUrlActionHandler constructor.
     *
     * @param \Manager\StorageManager $manager
     * @param \Manager\FileRegistry $registry
     * @param \Manager\Domain\TagManagerInterface $tagManager
     */
    public function __construct(
        StorageManager $manager,
        FileRegistry $registry,
        TagManagerInterface $tagManager
    ) {
        parent::__construct($manager, $registry, $tagManager);
    }

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
        $validationData = $this->handleValidation();

        $code = 418;
        $file = null;

        try {
            $file = $this->handleDownload($this->source, $validationData);
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

    protected function handleDownload(string $url, array $validationData): File
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

        try {
            $uploadedFile = $this->manager->storeFileFromRemoteSource(
                $adapter,
                $url,
                $validationData
            );

            $file = $this->registry->register($uploadedFile);

            foreach ($this->tags as $tag) {
                $this->tagManager->attachTagToFile($tag, $file);
            }

            return $file;
        } catch (UploadException $e) {
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    protected function handleValidation(): array
    {
        $this->assertValidUrl($this->source);

        $targetInfo = (new Client())->head($this->source, [
            'allow_redirects' => true,
        ]);

        if (200 !== $targetInfo->getStatusCode()) {
            throw new InvalidUrlException(sprintf(
                'Cannot retrieve remote content; status code: %d',
                $targetInfo->getStatusCode()
            ), 500);
        }

        return [
            'headResult' => $targetInfo
        ];
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
