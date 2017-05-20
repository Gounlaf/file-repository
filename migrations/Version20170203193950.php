<?php declare(strict_types=1);

namespace Db\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170203193950 extends BaseMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable($this->tablePrefix . 'tokens');

        $table->addColumn('id', 'string', [
            'length'  => 36,
            'notnull' => true,
            'fixed'   => true
        ]);

        $table->addColumn('roles', 'text', [
            'length' => 1024,
        ]);

        $table->addColumn('expiration_date', 'datetime');
        $table->addColumn('creation_date', 'datetime');

        $table->addIndex(['id']);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable($this->tablePrefix . 'tokens');
    }
}
