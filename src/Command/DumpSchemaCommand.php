<?php
namespace BrainDiminished\SchemaVersionControl\Command;

use BrainDiminished\SchemaVersionControl\SchemaVersionControlService;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to dump current schema from database to description file.
 */
class DumpSchemaCommand extends AbstractSchemaCommand
{
    protected function configure()
    {
        $this
            ->setName('schema:dump')
            ->setDescription('Dump current schema into schema description file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->schemaVersionControlService->dumpSchema();
    }
}
