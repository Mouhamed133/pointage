<?php
class Database
{
    private string $host = "localhost";
    private string $dbname = "pointagepro";
    private string $user = "root";
    private string $password = "";

    private ?PDO $pdo = null;

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
                    $this->user,
                    $this->password
                );
 
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
            } catch (PDOException $e) {
                die("Erreur de connexion à la base de données");
            }
        }

        return $this->pdo;
    }
}
