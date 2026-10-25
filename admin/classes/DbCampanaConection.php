<?php
declare(strict_types=1);

/**
 * Conexión a la base de datos del proyecto campaña (omarcampana).
 * Usada desde el webhook de WhatsApp para aceptar/rechazar invitaciones.
 */
class DbCampanaConection
{
    private string $host;
    private string $user;
    private string $pass;
    private string $dbName;
    private string $charset;
    private ?PDO $pdo = null;

    public function __construct()
    {
        $this->host = '82.197.83.171';
        $this->user = 'u533663207_ammispit345';
        $this->pass = 'Martin3933++$$++--$$';
        $this->dbName = 'u533663207_ammispit345';
        $this->charset = 'utf8mb4';
    }

    public function openConect(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

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
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ]
            );

            return $this->pdo;
        } catch (PDOException $e) {
            throw new Exception('No se pudo conectar a la base de la campaña.');
        }
    }

    public function closeConect(): void
    {
        $this->pdo = null;
    }

    public function getDbName(): string
    {
        return $this->dbName;
    }

    public function getTable(string $table): string
    {
        return $this->dbName . '.' . $table;
    }
}
