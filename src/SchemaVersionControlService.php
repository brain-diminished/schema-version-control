<?php
namespace BrainDiminished\SchemaVersionControl;

use BrainDiminished\SchemaVersionControl\Utils\SchemaBuilder;
use BrainDiminished\SchemaVersionControl\Utils\SchemaNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * SchemaVersionControlService is the main class of this library. It provides useful methods for managing a database
 * schema, whether to save the current and share current configuration in YAML format, or update the schema to match
 * a new configuration.
 */
class SchemaVersionControlService
{
    /** @var Connection */
    private $connection;

    /** @var string */
    private $schemaFile;

    /**
     * SchemaVersionControlService constructor.
     * @param Connection $connection
     * @param string $schemaFile Path to config file containing top object `schema`.
     */
    public function __construct(Connection $connection, string $schemaFile)
    {
        $this->connection = $connection;
        $this->schemaFile = $schemaFile;
    }

    /**
     * Get the current schema used in database.
     * @return Schema
     */
    public function getCurrentSchema(): Schema
    {
        return $this->connection->getSchemaManager()->createSchema();
    }

    /**
     * Load schema from config file.
     * @return Schema
     */
    public function loadSchemaFile(): Schema
    {
        if (!file_exists($this->schemaFile)) {
            return new Schema();
        }

        $content = file_get_contents($this->schemaFile);
        $desc = Yaml::parse($content);
        if (empty($desc)) {
            return new Schema();
        }

        $builder = new SchemaBuilder();
        return $builder->build($desc['schema']);
    }

    /**
     * Alter schema in database, according to config file.
     * @return int
     */
    public function applySchema()
    {
        $sqlStatements = $this->getMigrationSql();
        foreach ($sqlStatements as $sqlStatement) {
            $this->connection->exec($sqlStatement);
        }
        return count($sqlStatements);
    }

    /**
     * Get SQL statements needed to load config file in database
     * @return array
     */
    public function getMigrationSql(): array
    {
        $schemaDiff = $this->getSchemaDiff();
        return $schemaDiff->toSql($this->connection->getDatabasePlatform());
    }

    /**
     * Get diff between current schema and config file
     * @return SchemaDiff
     */
    public function getSchemaDiff(bool $reverseDiff = false): SchemaDiff
    {
        $currentSchema = $this->getCurrentSchema();
        $newSchema = $this->loadSchemaFile();
        $comparator = new Comparator();
        if ($reverseDiff) {
            return $comparator->compare($newSchema, $currentSchema);
        } else {
            return $comparator->compare($currentSchema, $newSchema);
        }
    }

    /**
     * Write current database schema in config file
     */
    public function dumpSchema()
    {
        $schema = $this->getCurrentSchema();
        $normalizer = new SchemaNormalizer();
        $desc = $normalizer->normalize($schema);
        $yamlSchema = Yaml::dump(['schema' =>$desc], 10, 2);
        $directory = dirname($this->schemaFile);
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($this->schemaFile, $yamlSchema);
    }
}
