<?php
namespace BrainDiminished\SchemaVersionControl\Test;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use TheCodingMachine\FluidSchema\DefaultNamingStrategy;
use TheCodingMachine\FluidSchema\FluidSchema;

abstract class AbstractSchemaTest extends TestCase
{
    /** @var Connection */
    protected static $dbConnection;

    public static function setUpBeforeClass()
    {
        self::resetConnection();
        $dbConnection = self::getConnection();
        self::initSchema($dbConnection);
    }

    protected static function resetConnection(): void
    {
        if (self::$dbConnection !== null) {
            self::$dbConnection->close();
        }
        self::$dbConnection = null;
    }

    protected static function getConnection(): Connection
    {
        if (self::$dbConnection === null) {
            $config = new Configuration();

            $connectionParams = [
                'user' => $GLOBALS['db_username'],
                'password' => $GLOBALS['db_password'],
                'host' => $GLOBALS['db_host'],
                'port' => $GLOBALS['db_port'],
                'driver' => $GLOBALS['db_driver']
            ];
            $adminConn = DriverManager::getConnection($connectionParams, $config);
            $adminConn->getSchemaManager()->dropAndCreateDatabase($GLOBALS['db_name']);

            $connectionParams['dbname'] = $GLOBALS['db_name'];
            self::$dbConnection = DriverManager::getConnection($connectionParams, $config);
        }
        return self::$dbConnection;
    }

    protected static function initSchema(Connection $connection)
    {
        $fromSchema = $connection->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;

        $db = new FluidSchema($toSchema, new DefaultNamingStrategy($connection->getDatabasePlatform()));

        $db->table('country')
            ->column('id')->integer()->primaryKey()->autoIncrement()->comment('@Autoincrement')
            ->column('label')->string(255);

        $db->table('person')
            ->column('id')->integer()->primaryKey()->autoIncrement()->comment('@Autoincrement')
            ->column('name')->string(255)
            ->column('modified_at')->datetime()->null()
            ->column('order')->integer()->null();

        $db->table('contact')
            ->extends('person')
            ->column('email')->string(255)
            ->column('manager_id')->references('contact')->null();

        $db->table('users')
            ->extends('contact')
            ->column('login')->string(255)
            ->column('password')->string(255)->null()
            ->column('status')->string(10)->null()->default(null)
            ->column('country_id')->references('country');

        $db->table('animal')
            ->column('id')->integer()->primaryKey()->autoIncrement()->comment('@Autoincrement')
            ->column('name')->string(45)->index()
            ->column('order')->integer()->null()
            ->then()->getDbalTable()->addOption('comment', 'test comment');

        $db->table('dog')
            ->extends('animal')
            ->column('owner_id')->references('person')
            ->column('race')->string(45)->null();

        $db->table('cat')
            ->extends('animal')
            ->column('cuteness_level')->integer()->null();

        $toSchema->getTable('users')
            ->addUniqueIndex([$connection->quoteIdentifier('login')], 'users_login_idx')
            ->addIndex([$connection->quoteIdentifier('status'), $connection->quoteIdentifier('country_id')], 'users_status_country_idx');

        $sqlStmts = $toSchema->getMigrateFromSql($fromSchema, $connection->getDatabasePlatform());

        foreach ($sqlStmts as $sqlStmt) {
            $connection->exec($sqlStmt);
        }
    }
}
