<?php

namespace Actions\Upload;

use Exception\ImageManager\FileNameReservedException;
use Exception\ImageManager\InvalidUrlException;
use Exception\Upload\DuplicatedContentException;
use Exception\Upload\UploadException;
use Manager\Domain\TagManagerInterface;
use Manager\FileRegistry;
use Manager\StorageManager;
use Model\AllowedMimeTypes;
use Model\Entity\File;

/**
 * @package Actions\Upload
 */
class UploadByHttpActionHandler extends AbstractUploadActionHandler
{
    /**
     * Force this file to be saved under this
     * file name, don't add any prefix if it already exists
     * and if it exists already then thrown an exception
     *
     * @var bool $forceFileName
     */
    private $forceFileName = false;

    /**
     * Form field name
     *
     * @var string
     */
    private $fieldName = 'upload';

    /**
     * @var int
     */
    private $maxFileSize = 1024 * 1024 * 300; // 300 kb

    /**
     * @var array
     */
    private $allowedMimes = [];

    /**
     * Decides if to be strict about the "move_uploaded_file" or not
     *
     * @var bool
     */
    private $strictUploadMode = true;

    /**
     *
     * @param \Manager\StorageManager $manager
     * @param \Manager\FileRegistry $registry
     * @param \Manager\Domain\TagManagerInterface $tagManager
     * @param \Model\AllowedMimeTypes $allowedMimes
     * @param int $allowedFileSize
     */
    public function __construct(
        StorageManager $manager,
        FileRegistry $registry,
        TagManagerInterface $tagManager,
        AllowedMimeTypes $allowedMimes,
        int $allowedFileSize
    ) {
        parent::__construct($manager, $registry, $tagManager);

        $this->allowedMimes = array_merge(
            [
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ],
            $allowedMimes->all()
        );

        $this->maxFileSize = $allowedFileSize;
    }

    /**
     * @param string $fileName
     * @param bool $forceFileName
     * @param array $tags
     *
     * @return UploadByHttpActionHandler
     */
    public function setData(string $fileName, array $tags = [], bool $forceFileName = false): UploadByHttpActionHandler
    {
        parent::setData($fileName, $tags);

        $this->forceFileName = $forceFileName;

        return $this;
    }

    /**
     * @throws FileNameReservedException
     * @throws InvalidUrlException
     * @throws \Exception\Upload\UploadException
     *
     * @return array
     */
    public function execute(): array
    {
        // Don't care about
        parent::execute();

        $code = 418;
        $file = null;

        try {
            $file = $this->handleUpload($_FILES[$this->fieldName]);
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
     * @inheritDoc
     */
    protected function handleValidation(): array
    {
        // TODO Replace me with Symfony/Component/Validator/Constraints/File
        // or Symfony/Component/Validator/Constraints/FileValidator
        if (!isset($_FILES[$this->fieldName])
            || is_array($_FILES[$this->fieldName]['error'])
        ) {
            throw new UploadException(
                'Error during the upload, reasons are two: ' .
                'The field was not sent, or there was an internal error details: ' .
                (isset($_FILES[$this->fieldName]['error']) ? json_encode($_FILES[$this->fieldName]['error']) : '')
            );
        }

        $uploadedFile = $_FILES[$this->fieldName];

        switch ($uploadedFile['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new UploadException('No file selected', UPLOAD_ERR_NO_FILE);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new UploadException('File size limit reached', UPLOAD_ERR_FORM_SIZE);
            default:
                throw new UploadException('Unknown error');
        }

        $mimeType = $this->manager->getMimeType($uploadedFile['tmp_name']);

        if (!in_array($mimeType, $this->allowedMimes)) {
            throw new UploadException('Invalid file format.');
        }

        if (filesize($uploadedFile['tmp_name']) >= $this->maxFileSize) {
            throw new UploadException('File size exceeds the limit');
        }

        // Return nothing
        return [];
    }

    /**
     * @param array $rawData a $_FILES entry
     *
     * @throws \Exception\Upload\UploadException
     * @throws \Exception\Upload\DuplicatedContentException;
     *
     * @return \Model\Entity\File
     */
    protected function handleUpload(array $rawData): File
    {
        if (!$this->registry->isDuplicateAllowed()) {
            // "High verification": rely on file content
            $hash = $this->manager->getHashFile($rawData['tmp_name']);

            if ($this->registry->existsInRegistryByHash($hash)) {
                $this->manager->cleanTmpFile($rawData);

                throw new DuplicatedContentException(
                    'Duplicate content',
                    $this->registry->getFileByContentHash($hash)
                );
            }
        }

        $adapter = $this->manager->chooseAdapterFromUpload($rawData);

        try {
            $uploadedFile = $this->manager->storeUploadedFile(
                $adapter,
                $rawData,
                $this->strictUploadMode
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
     * @param boolean $strictUploadMode
     *
     * @return UploadByHttpActionHandler
     */
    public function setStrictUploadMode(bool $strictUploadMode): UploadByHttpActionHandler
    {
        $this->strictUploadMode = $strictUploadMode;

        return $this;
    }

    /**
     * @param array $allowedMimes
     *
     * @return UploadByHttpActionHandler
     */
    public function setAllowedMimes(array $allowedMimes): UploadByHttpActionHandler
    {
        $this->allowedMimes = $allowedMimes;

        return $this;
    }
}
