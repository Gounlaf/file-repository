<?php

namespace Db\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170521153006 extends BaseMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $tTags        = $this->tablePrefix . 'tags';
        $tTagsNew     = $this->tablePrefix . 'tags_new';
        $tFileTags    = $this->tablePrefix . 'file_tags';
        $tFileTagsNew = $this->tablePrefix . 'file_tags_new';

        if ($schema->hasTable($tFileTagsNew)) {
            $schema->dropTable($tFileTags);
            $schema->renameTable($tFileTagsNew, $tFileTags);
        }

        if ($schema->hasTable($tTagsNew)) {
            $schema->dropTable($tTags);
            $schema->renameTable($tTagsNew, $tTags);
        }

        $table = $schema->getTable('core_migrations');

        $table->dropColumn('migration_name');
        $table->dropColumn('start_time');
        $table->dropColumn('end_time');
        $table->dropColumn('break_point');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // Can't rollback
    }
}
