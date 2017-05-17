<?php

class FileEnhancement extends BaseMigration
{
    public function change()
    {
        $table = $this->table($this->createTableName('file_registry'));

        $table->changeColumn('filename', 'string', [
            'length' => 255,// On most recent FS, it's usually this
        ]);

        $table->changeColumn('contentHash', 'string', [
            'length' => 64,// (sha256)
            'null' => false
        ]);

        $table->changeColumn('mimeType', 'string', [
            'length' => 127,// RFC-6838
            'null' => false
        ]);

        $table->addColumn('uuid', 'char', [// UUID type is not recognized by all DBMS
            'length' => 36,// UUID-v4
            'null' => false
        ]);

        $table->addColumn('adapterName', 'string', [
            'null' => false
        ]);

        $table->addColumn('path', 'string', [
            'null' => false
        ]);

        $table->addColumn('publicId', 'string', [
            'length' => 271,// UUID-v4 most significant bytes (16) + separator - (1) + filename (255)
            'null' => false
        ]);

        $table->addColumn('size', 'integer', [
            'null' => false
        ]);

        $table->addColumn('status', 'integer', [
            'null' => false
        ]);

        $table->addIndex(['uuid'], ['unique' => true]);
        // Don't had unique constraint; It should be, but field size make some trouble with, at least, MySQL
        $table->addIndex(['publicId']/*, ['unique' => true]*/);

        $table->removeIndex(['fileName']);
        $table->removeIndex(['contentHash']);

        $table->save();

        // Recreate tokens table

        $table = $this->table($this->createTableName('tokens'), ['id' => false]);
        $table->drop();

        $table = $this->table($this->createTableName('tokens'));

        $table->addColumn('uuid', 'binary', [
            'length' => 16,
            'null'   => false,
        ]);

        $table->addColumn('roles', 'text', [
            'length' => 1024,
        ]);

        $table->addColumn('data', 'text');

        $table->addColumn('expiration_date', 'datetime');
        $table->addColumn('creation_date', 'datetime');

        $table->create();
    }
}
