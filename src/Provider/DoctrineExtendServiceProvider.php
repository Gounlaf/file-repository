<?php

namespace Provider;

use Doctrine\DBAL\Types\Type;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

class DoctrineExtendServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        Type::addType('uuid_binary', UuidBinaryType::class);
    }

    public function boot(Application $app)
    {
        /** @var $dbConnection \Doctrine\DBAL\Connection */
        foreach ($app['dbs'] as $dbConnection) {
            $dbConnection->getDatabasePlatform()->registerDoctrineTypeMapping('uuid_binary', 'binary');
        }
    }
}
