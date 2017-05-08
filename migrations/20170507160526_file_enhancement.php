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
            'length' => 128,// based on SHA-2 algos
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

        $table->addIndex(['uuid'], ['unique' => true]);
        // Don't had unique constraint; It should be, but field size make some trouble with, at least, MySQL
        $table->addIndex(['publicId']/*, ['unique' => true]*/);

        $table->removeIndex(['fileName']);
        $table->removeIndex(['contentHash']);

        $table->save();
    }
}
