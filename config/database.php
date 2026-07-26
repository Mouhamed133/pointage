<?php
class Database
{
    private string $host;
    private string $dbname;
    private string $user;
    private string $password;
    private string $port;

    private ?PDO $pdo = null;

    public function __construct()
    {
        // Utiliser les variables d'environnement pour Railway, sinon utiliser les valeurs locales
        $this->host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost';
        $this->port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? '3306';
        $this->dbname = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'pointagepro';
        $this->user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '';
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
                
                $this->pdo = new PDO(
                    $dsn,
                    $this->user,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 5,
                    ]
                );
        
            } catch (PDOException $e) {
                error_log("Erreur de connexion à la base de données: " . $e->getMessage());
                die("Erreur de connexion à la base de données");
            }
        }

        return $this->pdo;
    }
}

