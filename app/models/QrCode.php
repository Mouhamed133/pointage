<?php

require_once __DIR__ . '/../../config/Database.php';

class QrCode
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function parUtilisateur(string $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM qr_codes WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existePourUtilisateur(string $userId): bool
    {
        return $this->parUtilisateur($userId) !== null;
    }

    public function trouverTokenValide(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT q.token, q.user_id FROM qr_codes q
            JOIN users u ON u.id = q.user_id
            WHERE q.token = ? AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creer(string $userId, string $token): bool
    {
        $stmt = $this->db->prepare("INSERT INTO qr_codes (user_id, token) VALUES (?, ?)");
        return $stmt->execute([$userId, $token]);
    }

    public function genererPourUtilisateur(string $userId, int $bytes = 4): string
    {
        $token = bin2hex(random_bytes($bytes));
        $this->creer($userId, $token);
        return $token;
    }

    public function genererSiAbsent(string $userId, int $bytes = 16): string
    {
        $existant = $this->parUtilisateur($userId);

        if ($existant) {
            return $existant['token'];
        }

        return $this->genererPourUtilisateur($userId, $bytes);
    }

    public function regenerer(string $userId, int $bytes = 4): string
    {
        $token = bin2hex(random_bytes($bytes));
        $stmt  = $this->db->prepare("UPDATE qr_codes SET token = ? WHERE user_id = ?");
        $stmt->execute([$token, $userId]);
        return $token;
    }
}