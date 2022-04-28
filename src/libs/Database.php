<?php
namespace Danilov;

class Database
{
    /**
     * @var PDO
     */
    protected static $db;

    /**
     * @var string
     */
    protected static $dsn;

    /**
     * @var string
     */
    protected static $username;

    /**
     * @var string
     */
    protected static $password;

    /**
     * Setting the necessary parameters to establish a connection to the database
     *
     * @param string $dsn      Database connection string
     * @param string $username Username for database
     * @param string $password Password for database
     *
     * @return void
     */
    public static function setup(string $dsn, string $username, string $password): void
    {
        self::$dsn = $dsn;
        self::$username = $username;
        self::$password = $password;
    }

    /**
     * Establishing a connection to the database
     *
     * @return void
     */
    public static function connect(): void
    {
        if (self::$db) { return; }

        self::$db = new \PDO(self::$dsn, self::$username, self::$password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => true
        ]);
    }

    /**
     * Closing a connection to the database
     *
     * @return void
     */
    public static function close(): void
    {
        self::$db = null;
    }

    /**
     * Returns an array containing all of the result set rows
     *
     * @param string $sql        SQL string
     * @param string $bind       Binding parameters
     * @param int    $fetchStyle Fetch style for output
     *
     * @return mixed
     */
    public static function getAll(string $sql, array $bind = [], ?int $fetchStyle = null)
    {
        $stmt = self::runQuery($sql, $bind);
        return $stmt->fetchAll($fetchStyle);
    }

    /**
     * Same as GetAll(), but $fetchStyle is set to PDO::FETCH_COLUMN
     *
     * @param string $sql  SQL string
     * @param string $bind Binding parameters
     *
     * @return mixed
     */
    public static function getCol(string $sql, array $bind = [])
    {
        return self::GetAll($sql, $bind, \PDO::FETCH_COLUMN);
    }

    /**
     * Returns a single column from the next row of a result set
     *
     * @param string $sql  SQL string
     * @param string $bind Binding parameters
     *
     * @return mixed
     */
    public static function getOne(string $sql, array $bind = [])
    {
        $stmt = self::runQuery($sql, $bind);
        return $stmt->fetchColumn();
    }

    /**
     * Same as GetAll(), but $fetchStyle is set to
     * PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC
     *
     * @param string $sql  SQL string
     * @param string $bind Binding parameters
     *
     * @return mixed
     */
    public static function getAssocRow(string $sql, array $bind = [])
    {
        // realization from: https://github.com/gabordemooij/redbean

        $query = self::GetAll($sql, $bind);
        $assoc = [];
        foreach ($query as $row) {
            if (empty($row)) {continue;}

            $key = array_shift($row);
            switch (count($row)) {
                case 0:
                    $value = $key;
                    break;
                case 1:
                    $value = reset($row);
                    break;
                default:
                    $value = $row;
            }

            $assoc[$key] = $value;
        }
        return $assoc;
    }

    /**
     * Fetches the next row from a result set
     *
     * @param string $sql        SQL string
     * @param string $bind       Binding parameters
     * @param int    $fetchStyle Fetch style for output
     *
     * @return mixed
     */
    public static function getRow(string $sql, array $bind = [], ?int $fetchStyle = null)
    {
        $stmt = self::runQuery($sql, $bind);
        return $stmt->fetch($fetchStyle);
    }

    /**
     * Executes a user prepared statement
     *
     * @param string $sql  SQL string
     * @param string $bind Binding parameters
     *
     * @return void
     */
    public static function execute(string $sql, array $bind = []): void
    {
        self::runQuery($sql, $bind);
    }

    /**
     * Initiates a transaction
     *
     * @return bool
     */
    public static function startTrans(): bool
    {
        self::connect();
        return self::$db->beginTransaction();
    }

    /**
     * Rolls back a transaction
     *
     * @return bool
     */
    public static function failTrans(): bool
    {
        self::connect();
        return self::$db->rollBack();
    }

    /**
     * Commits a transaction
     *
     * @return bool
     */
    public static function commitTrans(): bool
    {
        self::connect();
        return self::$db->commit();
    }

    /**
     * Get last insert id
     *
     * @return integer
     */
    public static function lastInsertId(): int
    {
        return self::$db->lastInsertId();
    }

    /**
     * Generate slots for bindValues
     *
     * @param array $elements Array of elements
     *
     * @return string
     */
    public static function genSlots(array $elements): string
    {
        return implode(',', array_fill(0, count($elements), '?'));
    }

    /**
     * Prepares the query and executes it
     *
     * @param string $sql  SQL string
     * @param string $bind Binding parameters
     *
     * @return PDOStatement
     */
    protected static function runQuery(string $sql, array $bind = []): \PDOStatement
    {
        self::connect();
        $stmt = self::$db->prepare($sql);
        self::bindValues($stmt, $bind);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Binding parameters for query
     * 
     * @param PDOStatement $stmt   PDO Statement instance
     * @param array        $values Binding parameters
     * 
     * @return void
     */
    protected static function bindValues(\PDOStatement $stmt, array $values = []): void
    {
        foreach ($values as $key => $value) {
            $k = is_int($key) ? $key + 1 : $key;
            if (is_null($value)) {
                $stmt->bindValue($k, null, \PDO::PARAM_NULL);
                continue;
            }

            $param_type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($k, $value, $param_type);
        }
    }
}
