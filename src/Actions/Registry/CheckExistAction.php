<?php

namespace Actions\Registry;

use Actions\AbstractBaseAction;
use Manager\FileRegistry;

/**
 * @package Actions\Registry
 */
class CheckExistAction extends AbstractBaseAction
{
    /**
     * @var \Manager\FileRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @param \Manager\FileRegistry $registry
     * @param string $fileName
     */
    public function __construct(FileRegistry $registry, string $fileName)
    {
        $this->registry = $registry;
        $this->fileName = $fileName;
    }

    /**
     * TODO Throw another "FileNotFoundException" (from a different/more appropriate namespace)
     *
     * @return array
     *
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function execute(): array
    {
        $collection = $this->registry->getFileByName($this->fileName);

        $data = [];
        foreach ($collection as $file) {
            $data[] = [
                'publicId' => $file->getPublicId(),
                'url'      => $this->registry->getFileUrl($file),
                'name'     => $file->getFileName(),
                'mime'     => $file->getMimeType(),
                'hash'     => $file->getContentHash(),
                'date'     => $file->getDateAdded()->format('Y-m-d H:i:s'),
            ];
        }

        return $data;
    }
}
