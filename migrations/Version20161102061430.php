<?php declare(strict_types=1);

namespace Db\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20161102061430 extends BaseMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->skipIf(!$schema->hasTable($this->tablePrefix . 'file_registry'),
            'Migration skipped for new installation');

        $table = $schema->createTable($this->tablePrefix . 'file_registry');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('fileName', 'string', [
            'length'  => 64,
            'notnull' => true,
            'fixed'   => true
        ]);
        $table->addColumn('contentHash', 'string', [
            'length' => 32,
        ]);
        $table->addColumn('dateAdded', 'datetime');
        $table->addColumn('mimeType', 'string', [
            'length'  => 24,
            'notnull' => true,
            'fixed'   => true
        ]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['fileName']);
        $table->addUniqueIndex(['contentHash']);
        $table->addIndex(['mimeType']);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable($this->tablePrefix . 'file_registry');
    }
}
