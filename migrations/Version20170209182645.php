<?php declare(strict_types=1);

namespace Db\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170209182645 extends BaseMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->skipIf(!$schema->hasTable($this->tablePrefix . 'tokens'), 'Migration skipped for new installation');

        $table = $schema->getTable($this->tablePrefix . 'tokens');
        $table->addColumn('data', 'text');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable($this->tablePrefix . 'tokens');
        $table->dropColumn('data');
    }
}
