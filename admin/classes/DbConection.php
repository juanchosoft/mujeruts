<?php
date_default_timezone_set('America/Bogota');

class DbConection
{
    private $host;
    private $user;
    private $pass;
    private $pdo;
    private $dbName;
    private $connection;
    private $server_date;
    private $charset;

    /**
     * Constructor que establece los datos de conexion a la base de datos
     */
    public function __construct()
    {
        /** Local */
        $this->host = "localhost";
        $this->user = "root";
        $this->pass = "";
        $this->dbName = "mujer";
        $this->charset = "utf8mb4";

        // Este es el timestamp que se debe ingresar, de acuerdo a la hora deseada
        $this->server_date = 'DATE_ADD(NOW(),INTERVAL 1 HOUR)';
        $this->pdo = null;
    }

    /**
     * Establece la conexion con la base de datos
     */
    public function openConect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";

            $this->pdo = new PDO(
                $dsn,
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );

            return $this->pdo;
        } catch (PDOException $e) {
            throw new Exception(
                "<p>Error: No puede conectarse con la base de datos.</p><p>" .
                $e->getMessage() .
                "</p>\n"
            );
        }
    }

    /**
     * Cierra la conexion con la base de datos
     */
    public function closeConect()
    {
        $this->pdo = null;
    }

    public function getServerDate()
    {
        return $this->server_date;
    }

    public function getDbName()
    {
        return $this->dbName;
    }

    public function getTable($table)
    {
        return $this->dbName . "." . $table;
    }
}
?>