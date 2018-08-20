<?php
namespace BrainDiminished\SchemaVersionControl\Command;

use BrainDiminished\SchemaVersionControl\SchemaVersionControlService;
use Symfony\Component\Console\Command\Command;

abstract class AbstractSchemaCommand extends Command
{
    /** @var SchemaVersionControlService */
    protected $schemaVersionControlService;

    public function __construct(SchemaVersionControlService $schemaVersionControlService)
    {
        parent::__construct();
        $this->schemaVersionControlService = $schemaVersionControlService;
    }
}