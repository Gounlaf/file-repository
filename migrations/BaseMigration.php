<?php declare(strict_types=1);

namespace Db\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\ORM\Events;

use Doctrine\Extension\TablePrefix;

abstract class BaseMigration extends AbstractMigration
{
    protected $tablePrefix = '';

    public function __construct(Version $version)
    {
        parent::__construct($version);

        foreach ($this->connection->getEventManager()->getListeners(Events::loadClassMetadata) as $listener) {
            if ($listener instanceof TablePrefix) {
                $this->tablePrefix = $listener->getPrefix();
                break;
            }
        }
    }
}
