<?php

namespace Actions\Registry;

use Actions\AbstractBaseAction;
use Manager\FileRegistry;

/**
 * Allows to delete a file from the repository
 *
 * @package Actions\Registry
 */
class DeleteAction extends AbstractBaseAction
{
    /**
     * @var \Manager\FileRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $publicId;

    /**
     * @param \Manager\FileRegistry $registry
     * @param string $publicId
     */
    public function __construct(FileRegistry $registry, string $publicId)
    {
        $this->registry = $registry;
        $this->publicId = $publicId;
    }

    /**
     * TODO Throw another "FileNotFoundException" (from a different/more appropriate namespace)
     * TODO Throw a more specific exception
     *
     * @return array
     *
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @throws \RuntimeException
     */
    public function execute(): array
    {
        $file = $this->registry->getFileByPublicId($this->publicId);
        $this->registry->deleteFile($file);

        return [
            'hash' => $file->getContentHash(),
        ];
    }
}
