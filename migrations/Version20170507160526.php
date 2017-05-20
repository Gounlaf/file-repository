<?php declare(strict_types=1);

namespace Db\Migrations;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class Version20170507160526 extends BaseMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable($this->tablePrefix . 'file_registry');
        $table->changeColumn('fileName', [
            'length' => 255,// On most recent FS, it's usually this
        ]);

        $table->changeColumn('contentHash', [
            'length'  => 64,// (sha256)
            'notnull' => true,
            'fixed'   => true
        ]);

        $table->changeColumn('mimeType', [
            'length' => 127,// RFC-6838
            'fixed'  => true
        ]);

        $table->addColumn('uuid', 'string', [// UUID type is not recognized by all DBMS
            'length'  => 36,// UUID-v4
            'notnull' => true,
            'fixed'   => true
        ]);

        $table->addColumn('adapterName', 'string', [
            'notnull' => true
        ]);

        $table->addColumn('path', 'string', [
            'notnull' => true
        ]);

        $table->addColumn('publicId', 'string', [
            'length'  => 271,// UUID-v4 most significant bytes (16) + separator - (1) + filename (255)
            'notnull' => true,
//            'fixed'   => true
        ]);

        $table->addColumn('size', 'integer', [
            'notnull' => true
        ]);

        $table->addColumn('status', 'integer', [
            'notnull' => true
        ]);

        $table->addUniqueIndex(['uuid']);
        // Don't had unique constraint; It should be, but field size make some trouble with, at least, MySQL
        $table->addIndex(['publicId']);


        $indexesToRemove = [
            new Index('index_fileName', array('fileName')),
            new Index('index_contentHash', array('contentHash')),
        ];

        foreach ($table->getIndexes() as $index) {
            foreach ($indexesToRemove as $i => $indexToRemove) {
                if ($index->isFullfilledBy($indexToRemove)) {
                    $table->dropIndex($index->getName());
                    unset($indexesToRemove[$i]);
                    break;
                }
            }
        }

        // Recreate tokens table
        $schema->dropTable($this->tablePrefix . 'tokens');

        $table = $schema->createTable($this->tablePrefix . 'tokens');

        $table->addColumn('uuid', 'binary', [
            'length'  => 16,
            'notnull' => true,
        ]);

        $table->addColumn('roles', 'text', [
            'length' => 1024,
        ]);

        $table->addColumn('data', 'text');
        $table->addColumn('expiration_date', 'datetime');
        $table->addColumn('creation_date', 'datetime');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable($this->tablePrefix . 'file_registry');

        $table->changeColumn('fileName', [
            'length'  => 64,
            'notnull' => true,
            'fixed'   => true,
        ]);

        $table->changeColumn('contentHash', [
            'length'  => 32,
            'notnull' => true,
            'fixed'   => true
        ]);

        $table->changeColumn('mimeType', [
            'length'  => 24,
            'notnull' => true,
            'fixed'   => true
        ]);

        $table->dropColumn('uuid');
        $table->dropColumn('adapterName');
        $table->dropColumn('path');
        $table->dropColumn('publicId');
        $table->dropColumn('size');
        $table->dropColumn('status');

        $indexesToRemove = [
            new Index('index_uuid', array('uuid')),
            new Index('index_publicId', array('publicId')),
        ];

        foreach ($table->getIndexes() as $index) {
            foreach ($indexesToRemove as $i => $indexToRemove) {
                if ($index->isFullfilledBy($indexToRemove)) {
                    $table->dropIndex($index->getName());
                    unset($indexesToRemove[$i]);
                    break;
                }
            }
        }

        (new Version20170203193950($this->version))->up($schema);
    }
}
