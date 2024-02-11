<?php
namespace BrainDiminished\SchemaVersionControl\Command;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to view status of database
 */
class SchemaStatusCommand extends AbstractSchemaCommand
{
    protected function configure(): void
    {
        $this
            ->setName('schema:status')
            ->setDescription('Show database schema status.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $diff = $this->schemaVersionControlService->getSchemaDiff(true);

        if (empty($diff->changedTables)
            && empty($diff->newTables)
            && empty($diff->removedTables)){
            $output->writeln('Schema up-to-date.');
            return 0;
        }

        foreach ($diff->changedTables as $changedTable) {
            $output->writeln("modified: table `$changedTable->name`");
            $this->tableDiffStatus($changedTable, $input, $output);
            $output->writeln('');
        }

        foreach ($diff->newTables as $newTable) {
            $name = $newTable->getName();
            $output->writeln("added: table `$name`");
        }

        foreach ($diff->removedTables as $removedTable) {
            $name = $removedTable->getName();
            $output->writeln("deleted: table `$name`");
        }

        $output->writeln('');
        $output->writeln('You need to synchronize your database schema with your schema file:');
        $output->writeln('- Command `schema:dump` to save local changes into schema file.');
        $output->writeln('- Command `schema:apply` to migrate database version/discard local changes.');
        $output->writeln('- Manual migration (you can start from a SQL script obtained by using command `schema:apply --dry-run`.');
        return 0;
    }

    protected function tableDiffStatus(TableDiff $tableDiff, InputInterface $input, OutputInterface $output)
    {
        foreach ($tableDiff->changedColumns as $changedColumn) {
            $name = $changedColumn->column->getName();
            $output->writeln("  Properties changed on column `$name`.");
        }
        foreach ($tableDiff->renamedColumns as $oldName => $renamedColumn) {
            $name = $renamedColumn->getName();
            $output->writeln("  Column `$oldName` renamed to `$name`.");
        }
        foreach ($tableDiff->addedColumns as $addedColumn) {
            $name = $addedColumn->getName();
            $output->writeln("  Added column `$name`.");
        }
        foreach ($tableDiff->removedColumns as $removedColumn) {
            $name = $removedColumn->getName();
            $output->writeln("  Removed column `$name`.");
        }
        foreach ($tableDiff->changedIndexes as $changedIndex) {
            $name = $changedIndex->getName();
            $output->writeln("  Modified index `$name` on ".$this->descIndex($changedIndex));
        }
        foreach ($tableDiff->renamedIndexes as $oldName => $renamedIndex) {
            $name = $renamedIndex->getName();
            $output->writeln("  Index `$oldName` renamed to `$name`.");
        }
        foreach ($tableDiff->addedIndexes as $addedIndex) {
            $name = $addedIndex->getName();
            $output->writeln("  Added index `$name` on ".$this->descIndex($addedIndex));
        }
        foreach ($tableDiff->removedIndexes as $removedIndex) {
            $name = $removedIndex->getName();
            $output->writeln("  Removed index `$name` on ".$this->descIndex($removedIndex));
        }
        foreach ($tableDiff->changedForeignKeys as $changedForeignKey) {
            $name = $changedForeignKey->getName();
            $output->writeln("  Modified foreign key `$name`: ".$this->descForeignKey(($changedForeignKey)));
        }
        foreach ($tableDiff->addedForeignKeys as $addedForeignKey) {
            $name = $addedForeignKey->getName();
            $output->writeln("  Added foreign key `$name`: ".$this->descForeignKey($addedForeignKey));
        }
        foreach ($tableDiff->removedForeignKeys as $removedForeignKey) {
            $name = $removedForeignKey->getName();
            $output->writeln("  Removed foreign key `$name`: ".$this->descForeignKey($removedForeignKey));
        }
    }

    protected function descIndex(Index $index)
    {
        $columns = $index->getColumns();
        if (count($columns) == 1) {
            $list = '`'.$columns[0].'`';
        } else {
            $list = '(`'.implode('`,`', $columns).'`)`';
        }

        if ($index->isUnique()) {
            return "UNIQUE $list";
        } else {
            return $list;
        }

    }

    protected function descForeignKey(ForeignKeyConstraint $foreignKey)
    {
        $localColumns = $foreignKey->getLocalColumns();
        $foreignColumns = $foreignKey->getForeignColumns();
        $foreignTable = $foreignKey->getForeignTableName();
        if (count($localColumns) == 1) {
            $localColumn = $localColumns[0];
            $foreignColumn = $foreignColumns[0];
            return "`$localColumn` => `$foreignTable`.`$foreignColumn`";
        } else {
            $localList = '(`'.implode('`,`', $localColumns).'`)`';
            $foreignList = '(`'.implode('`,`', $foreignColumns).'`)`';
            return "$localList => `$foreignTable` $foreignList";
        }
    }
}