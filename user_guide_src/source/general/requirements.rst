###################
Server Requirements
###################

`PHP <https://secure.php.net/>`_ version 8.4 or newer is required.

A database is required for most web application programming.
Currently supported databases are:

  - MySQL (5.1+) via the *mysqli* and *pdo* drivers
  - Oracle via the *pdo* drivers
  - PostgreSQL via the *postgre* and *pdo* drivers
  - MS SQL via the *sqlsrv* (version 2005 and above only) and *pdo* drivers
  - SQLite via the *sqlite3* and *pdo* drivers
  - ODBC via the *pdo* drivers (you should know that ODBC is actually an abstraction layer)

The following legacy drivers are **deprecated** and emit ``E_USER_DEPRECATED``
when used. They rely on PHP extensions removed in PHP 8.0 and are scheduled
for removal:

  - *mysql* (removed from PHP 7.0)
  - *cubrid*, *mssql*, *ibase*, *oci8*, *odbc* (extensions removed from PHP 8.0)

Use the *mysqli* or *pdo* drivers instead.