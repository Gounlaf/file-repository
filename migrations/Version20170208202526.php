<?php declare(strict_types=1);

namespace Db\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170208202526 extends BaseMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable($this->tablePrefix . 'tags');
        $table->addColumn('id', 'string', [
            'length' => 36,
            'fixed'  => true
        ]);

        $table->addColumn('name', 'string', [
            'length'  => 48,
            'notnull' => false,
            'fixed'   => true
        ]);

        $table->addColumn('dateAdded', 'datetime');
        $table->setPrimaryKey(['id']);


        $middleTable = $schema->createTable($this->tablePrefix . 'file_tags');
        $middleTable->addColumn('file_id', 'string', [
            'length' => 36,
            'fixed'  => true
        ]);
        $middleTable->addColumn('tag_id', 'string', [
            'length' => 36,
            'fixed'  => true
        ]);

        $middleTable->setPrimaryKey(['file_id', 'tag_id']);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable($this->tablePrefix . 'file_tags');
        $schema->dropTable($this->tablePrefix . 'tags');
    }
}
