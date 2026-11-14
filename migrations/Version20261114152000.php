<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cook request planning tables.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();

        if ($fromSchema->hasTable('cook_requests') && $fromSchema->hasTable('cook_request_entries')) {
            return;
        }

        $toSchema = clone $fromSchema;

        $cookRequests = $toSchema->createTable('cook_requests');
        $cookRequests->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $cookRequests->addColumn('owner_id', Types::INTEGER);
        $cookRequests->addColumn('title', Types::STRING, ['length' => 255]);
        $cookRequests->addColumn('status', Types::STRING, ['length' => 16]);
        $cookRequests->addColumn('position', Types::INTEGER);
        $cookRequests->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $cookRequests->addColumn('completed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $cookRequests->setPrimaryKey(['id']);
        $cookRequests->addIndex(['owner_id'], 'IDX_BFEC198D7E3C61F9');
        $cookRequests->addForeignKeyConstraint('user', ['owner_id'], ['id'], ['onDelete' => 'CASCADE']);

        $entries = $toSchema->createTable('cook_request_entries');
        $entries->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $entries->addColumn('cook_request_id', Types::INTEGER);
        $entries->addColumn('recipe_id', Types::INTEGER);
        $entries->addColumn('position', Types::INTEGER);
        $entries->setPrimaryKey(['id']);
        $entries->addIndex(['cook_request_id'], 'IDX_E60C68AF642829FE');
        $entries->addIndex(['recipe_id'], 'IDX_E60C68AF59D8A214');
        $entries->addUniqueIndex(['cook_request_id', 'recipe_id'], 'uniq_cook_request_recipe');
        $entries->addForeignKeyConstraint('cook_requests', ['cook_request_id'], ['id'], ['onDelete' => 'CASCADE']);
        $entries->addForeignKeyConstraint('recipes', ['recipe_id'], ['id'], ['onDelete' => 'CASCADE']);

        $comparator = new Comparator();
        $diff = $comparator->compareSchemas($fromSchema, $toSchema);

        foreach ($diff->toSaveSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cook_request_entries');
        $this->addSql('DROP TABLE cook_requests');
    }
}
