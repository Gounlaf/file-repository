<?php declare(strict_types=1);

namespace Flysystem\Plugins;

use League\Flysystem\Adapter\Local;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Plugin\AbstractPlugin;

class IsLocal extends AbstractPlugin
{
    protected $isLocal = false;

    /**
     * @inheritdoc
     *
     * @throws \Exception\Flysystem\InvalidArgumentException
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        parent::setFilesystem($filesystem);

        $reflection = new \ReflectionObject($filesystem);
        if ($reflection->hasMethod('getAdapter')) {
            $method = $reflection->getMethod('getAdapter');

            if ($method->isPublic()) {
                /* @var $adapter \League\Flysystem\AdapterInterface */
                $adapter = $method->invoke($filesystem);

                $this->isLocal = $adapter instanceof Local;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getMethod(): string
    {
        return 'isLocal';
    }

    /**
     * @return bool
     */
    public function handle(): bool
    {
        return $this->isLocal;
    }
}
