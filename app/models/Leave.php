<?php

require_once __DIR__ . '/../../config/Database.php';

class Leave
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.nom, u.email, u.department, r.nom as reviewer_nom
            FROM leaves l
            JOIN users u ON u.id = l.user_id
            LEFT JOIN users r ON r.id = l.reviewer_id
            WHERE l.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function liste(string $statut = ''): array
    {
        $sql = "
            SELECT l.*, u.nom, u.email, u.department,
                   r.nom as reviewer_nom
            FROM leaves l
            JOIN users u ON u.id = l.user_id
            LEFT JOIN users r ON r.id = l.reviewer_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($statut)) {
            $sql .= " AND l.status = ?";
            $params[] = $statut;
        }

        $sql .= " ORDER BY l.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pourEtudiant(string $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare("SELECT * FROM leaves WHERE user_id = ? ORDER BY id DESC LIMIT $limit");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existePourDate(string $userId, string $date): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM leaves WHERE user_id = ? AND start_date = ? LIMIT 1");
        $stmt->execute([$userId, $date]);
        return (bool) $stmt->fetch();
    }

    public function pourMois(string $anneeMois): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.nom, u.email, u.department, r.nom as reviewer_nom
            FROM leaves l
            JOIN users u ON u.id = l.user_id
            LEFT JOIN users r ON r.id = l.reviewer_id
            WHERE l.start_date LIKE ?
            ORDER BY l.start_date DESC
        ");
        $stmt->execute([$anneeMois . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocument(int $id): ?string
    {
        $stmt = $this->db->prepare("SELECT document FROM leaves WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['document'] ?? null;
    }

    public function creer(
        string $userId,
        string $type,
        string $startDate,
        string $endDate,
        string $reason = '',
        ?string $document = null,
        ?string $dateAbsence = null
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO leaves (user_id, type, status, start_date, end_date, reason, document, date_absence)
            VALUES (?, ?, 'en_attente', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $type, $startDate, $endDate, $reason, $document, $dateAbsence ?? $startDate]);

        return (int) $this->db->lastInsertId();
    }

    public function approuver(int $id, string $reviewerId): bool
    {
        $stmt = $this->db->prepare("UPDATE leaves SET status = 'approuve', reviewer_id = ? WHERE id = ?");
        return $stmt->execute([$reviewerId, $id]);
    }

    public function refuser(int $id, string $reviewerId): bool
    {
        $stmt = $this->db->prepare("UPDATE leaves SET status = 'refuse', reviewer_id = ? WHERE id = ?");
        return $stmt->execute([$reviewerId, $id]);
    }

    public function supprimerCommeEtudiant(int $id, string $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM leaves WHERE id = ? AND user_id = ? AND status = 'en_attente'");
        return $stmt->execute([$id, $userId]);
    }

    public function supprimerCommeAdmin(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM leaves WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getNomEtudiant(int $id): ?string
    {
        $stmt = $this->db->prepare("SELECT u.nom FROM leaves l JOIN users u ON u.id = l.user_id WHERE l.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['nom'] ?? null;
    }
}