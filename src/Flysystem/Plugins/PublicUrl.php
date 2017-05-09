<?php declare(strict_types=1);

namespace Flysystem\Plugins;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\Plugin\AbstractPlugin;

use Exception\Flysystem\InvalidArgumentException;

class PublicUrl extends AbstractPlugin
{
    protected $publicUrl = '';

    /**
     * @inheritdoc
     *
     * @throws \Exception\Flysystem\InvalidArgumentException
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $reflection = new \ReflectionObject($filesystem);
        if (!$reflection->hasMethod('getConfig')) {
            throw new InvalidArgumentException('Given filesystem does not have method getConfig()');
        }

        if (!$reflection->getMethod('getConfig')->isPublic()) {
            throw new InvalidArgumentException('Given filesystem dont let access to his configuration');
        }

        parent::setFilesystem($filesystem);

        /* @var $config \League\Flysystem\Config */
        $config = $this->filesystem->getConfig();

        $this->publicUrl = $config->get('publicUrl', '');
    }

    /**
     * @inheritdoc
     */
    public function getMethod(): string
    {
        return 'getPublicUrl';
    }

    /**
     * @return string
     */
    public function handle(): string
    {
        return $this->publicUrl;
    }
}
