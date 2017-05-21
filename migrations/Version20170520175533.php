<?php declare(strict_types=1);

namespace Db\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170520175533 extends BaseMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $tTags        = $this->tablePrefix . 'tags';
        $tTagsNew     = $this->tablePrefix . 'tags_new';
        $tFileTags    = $this->tablePrefix . 'file_tags';
        $tFileTagsNew = $this->tablePrefix . 'file_tags_new';

        if ($schema->hasTable($tFileTagsNew) && $schema->hasTable($tTagsNew)) {
            foreach ($this->connection->fetchAll('SELECT * FROM ' . $tTags . ' ORDER BY dateAdded ASC') as $row) {
                $this->addSql(
                    'INSERT INTO ' . $tTagsNew . ' (name,dateAdded) VALUES (:name, :dateAdded)',
                    ['name' => $row['name'], 'dateAdded' => $row['dateAdded']]
                );
            }

            foreach ($this->connection->fetchAll('SELECT * FROM ' . $tFileTags . ' ORDER BY file_id') as $row) {
                $this->addSql(
                    'INSERT INTO ' . $tFileTagsNew
                    . ' SELECT :fileId, ' . $tTagsNew . '.id FROM ' . $tTagsNew . ' WHERE name = (SELECT name FROM ' . $tTags . ' WHERE id = :oldTagId)',

                    ['fileId' => $row['file_id'], 'oldTagId' => $row['tag_id']]
                );
            }
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
