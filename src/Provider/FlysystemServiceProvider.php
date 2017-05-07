<?php declare(strict_types=1);

namespace Provider;

use \ReflectionClass;

use League\Flysystem;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Structure\Structure;

use Exception\Flysystem\ConfigurationException;
use Flysystem\Plugins\PublicUrl;

class FlysystemServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $config = $pimple['storage.flysystem'];

        $this->checkConfig($config);

        $instances = array();

        foreach ($config as $systemKey => $systemConfig) {
            $reflection = new ReflectionClass($systemConfig['adapter']['type']);
            /* @var $adapter \League\Flysystem\AdapterInterface */
            $adapter = $reflection->newInstanceArgs($systemConfig['adapter']['config']);

            $filesystem = new Flysystem\Filesystem(
                $adapter,
                $systemConfig['config']
            );

            $filesystem->addPlugin(new PublicUrl());

            $instances[$systemKey] = $filesystem;
        }

        $pimple->offsetSet('flysystem.instances', $instances);
    }

    /**
     * @param array $config
     * @throws \Exception\Flysystem\ConfigurationException
     */
    protected function checkConfig(array $config)
    {
        $arrayCheck = Structure::ArrayS(array(
            'adapter' => array(
                'type'   => 'string',
                'config' => 'array',
            ),
            'config'  => 'array'
        ), $config);

        foreach ($config as $systemKey => $systemConfig) {
            if (!$arrayCheck->check($systemConfig, $failed)) {
                throw new ConfigurationException(sprintf(
                    'Wrong Flysystem configuration for system "%s"; raw validation output: %s',
                    $systemKey,
                    var_export($failed, true)
                ));
            }

            if (!class_exists($systemConfig['adapter']['type'])) {
                throw new ConfigurationException(sprintf(
                    'Wrong Flysystem configuration for system "%s"; Class "%s" does not exist',
                    $systemKey,
                    $systemConfig['adapter']['type']
                ));
            }

            $reflection = new ReflectionClass($systemConfig['adapter']['type']);
            if (!$reflection->implementsInterface(Flysystem\AdapterInterface::class)) {
                throw new ConfigurationException(sprintf(
                    'Wrong Flysystem configuration for system "%s"; Class "%s" does not implement Flysystem\AdapterInterface',
                    $systemKey,
                    $systemConfig['adapter']['type']
                ));
            }
        }
    }
}
