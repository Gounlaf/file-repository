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
        $table = $schema->getTable('core_migrations');

        $table->dropColumn('migration_name');
        $table->dropColumn('start_time');
        $table->dropColumn('end_time');
        $table->dropColumn('break_point');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        // NOOP
    }
}
