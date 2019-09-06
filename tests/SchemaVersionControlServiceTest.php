<?php
namespace BrainDiminished\SchemaVersionControl\Test;

use BrainDiminished\SchemaVersionControl\SchemaVersionControlService;

class SchemaVersionControlServiceTest extends AbstractSchemaTest
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testLoad()
    {
        $schemaVersionControlService = new SchemaVersionControlService(self::$dbConnection, __DIR__.'/etc/schema1.yml');
        $schema = $schemaVersionControlService->loadSchemaFile();
        $tables = $schema->getTables();
        $this->assertEquals(2, count($tables));
        $animalTable = $schema->getTable('animal');
        $this->assertEquals(['id'], $animalTable->getPrimaryKeyColumns());
    }

    public function testIndexes()
    {
        $schemaVersionControlService = new SchemaVersionControlService(self::$dbConnection, __DIR__.'/var/schema2.yml');
        $schema = $schemaVersionControlService->loadSchemaFile();

        $userTable = $schema->getTable('users');
        $this->assertTrue($userTable->getIndex("users_login_idx")->isUnique());
        $this->assertFalse($userTable->getIndex("users_status_country_idx")->isUnique());
    }

    public function testComment()
    {
        $schemaVersionControlService = new SchemaVersionControlService(self::$dbConnection, __DIR__.'/var/schema2.yml');
        $schema = $schemaVersionControlService->loadSchemaFile();

        $animalTable = $schema->getTable('animal');
        $this->assertTrue($animalTable->hasOption('comment'));
        $this->assertEquals('test comment', $animalTable->getOption('comment'));
    }

    public function testDump()
    {
        $schemaFile = __DIR__.'/var/schema2.yml';
        if (file_exists($schemaFile)) {
            unlink($schemaFile);
        }
        $schemaVersionControlService = new SchemaVersionControlService(self::$dbConnection, $schemaFile);
        $schemaVersionControlService->dumpSchema();
        $this->assertFileExists($schemaFile);
    }

    public function testMigrateSql()
    {
        $schemaVersionControlService = new SchemaVersionControlService(self::$dbConnection, __DIR__.'/etc/schema1.yml');
        $sqlStatements = $schemaVersionControlService->getMigrationSql();
        $this->assertNotEmpty($sqlStatements);
    }
}
