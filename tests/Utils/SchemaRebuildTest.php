<?php
namespace BrainDiminished\SchemaVersionControl\Test\Utils;

use BrainDiminished\SchemaVersionControl\Utils\SchemaBuilder;
use BrainDiminished\SchemaVersionControl\Utils\SchemaNormalizer;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;
use TheCodingMachine\FluidSchema\FluidSchema;

class SchemaRebuildTest extends TestCase
{
    protected function constructSchema()
    {
        $schema = new FluidSchema(new Schema());

        $schema->table('companies')
            ->id()
            ->column('name')->string(255)->notNull()->unique()
        ;

        $schema->table('stores')
            ->id()
            ->column('company_id')->references('companies')
            ->column('city')->string(255)
            ->column('address')->string(255)
        ;

        $schema->table('persons')
            ->id()
            ->column('firstname')->string(255)
            ->column('lastname')->string(255)
            ->column('email')->string(255)->notNull()->unique()
            ->column('employer_id')->references('stores')->null()->comment('@Many "employees"')
        ;

        $schema->table('stores_clients')
            ->column('store_id')->references('stores')
            ->column('client_id')->references('persons')
            ->then()
            ->primaryKey(['store_id', 'client_id'])
        ;

        return $schema->getDbalSchema();
    }

    public function testRebuild()
    {
        $schema = $this->constructSchema();

        $normalizer = new SchemaNormalizer();
        $normalizedSchema = $normalizer->normalize($schema);

        $builder = new SchemaBuilder();
        $reconstructedSchema = $builder->build($normalizedSchema);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($schema, $reconstructedSchema);

        $sqlStatements = $reconstructedSchema->getMigrateFromSql($schema, new MySQL57Platform());
        $this->assertEmpty($sqlStatements);
    }
}
