# Schema Version Control

This package provides a set of tools useful for managing your database schemas, more precisely when it comes to sharing
your model within a versioned project. In order to do so, the model will actually be tracked as a YAML file.

## Service

The main class of this project is `SchemaVersionControlService`; this service provides operations involving
`Doctrine\DBAL\Schema\Schema` objects. The constructor of `SchemaVersionControlService` needs two parameters: a
`Connection` object to be able to inspect current database schema (please note that this connection should be allowed
to create/update/delete tables), as well as the path to your schema file (which might not exist yet when initializing
the project).
The two principal methods of this class are:
- `applySchema()`: this method loads the schema described in your schema file (supposedly versioned), compares it with
the actual schema in database, and executes the SQL statements necessary to solve the differences. You should use this
method when updating your source code, assuming that you haven't changed your model locally (if so, you may want to
refer to [Resolve Conflicts](#resolve_conflicts) section).
- `dumpSchema()`: this method, opposed to `applySchema()`, inspects the current schema used in database, and uses it to
change the content of your schema file. You should use this method when modifying your model locally, assuming that your
model was up-to-date before you started the modifications (otherwise, once again, refer to [Resolve
Conflicts](#resolve_conflicts) section).

In addition, you will find a few other methods, such as `getMigrationSql()` to compute the SQL statements to execute in
order to resynchronize your database schema with your schema file, or `getSchemaDiff()` to compute the `SchemaDiff`
object representing the difference between the actual database schema and the one described in the schema file.

<a name="resolve_conflicts"></a>
## Resolve conflicts

It is advised that you update and commit your schema before updating your sources. Therefore, you will simply have to
resolve the conflicts (if any) in the YAML file, which should not be too complicated.

If you did not commit before pulling sources, applying the schema from YAML file will most certainly end up removing
your modification, which you probably do not want. First dump your schema, review the modifications in YAML file
(recently pulled changes will have been reverted), and only keep those that were actually your doing. You should then be
safe for applying the newly obtained version of your schema.

## Commands

In order to be used through Symfony console, this package provides these three commands:
- `schema:dump` (cf. `SchemaVersionControlService::dump()`).
- `schema:apply` (cf. `SchemaVersionControlService::apply()`): Note that option `--dry-run` may be useful, particularly
when you need to update the schema of an application running in production.
- `schema:status`: Displays all changes performed on actual schema, relatively to YAML schema file. Therefore, all
differences between the two schemas are considered as local changes, even when they actually result from pulled changes
on YAML file.
