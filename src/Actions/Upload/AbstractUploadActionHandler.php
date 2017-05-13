<?php

namespace Actions\Upload;

use Actions\AbstractBaseAction;
use Manager\Domain\TagManagerInterface;
use Manager\FileRegistry;
use Manager\StorageManager;

abstract class AbstractUploadActionHandler extends AbstractBaseAction
{
    /**
     * @var \Manager\FileRegistry
     */
    protected $registry;

    /**
     * @var \Manager\StorageManager
     */
    protected $manager;

    /**
     * @var \Manager\Domain\TagManagerInterface
     */
    protected $tagManager;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string[]
     */
    protected $tags = [];

    /**
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
        $this->manager     = $manager;
        $this->registry    = $registry;
        $this->tagManager  = $tagManager;
    }

    /**
     * @param string $source
     * @param array $tags
     *
     * @return $this
     */
    public function setData(string $source,  array $tags = [])
    {
        $this->source    = $source;
        $this->tags       = $tags;

        return $this;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        return $this->handleValidation();
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    abstract protected function handleValidation(): array;
}
