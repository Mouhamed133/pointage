<?php

require_once __DIR__ . '/../../config/Database.php';

class Attendance
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function duJour(string $userId, ?string $date = null): ?array
    {
        $date = $date ?? date('Y-m-d');
        $stmt = $this->db->prepare("SELECT * FROM attendances WHERE user_id = ? AND date = ? LIMIT 1");
        $stmt->execute([$userId, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existePourDate(string $userId, string $date): bool
    {
        return $this->duJour($userId, $date) !== null;
    }

    public function historique(string $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM attendances
            WHERE user_id = ?
            ORDER BY date DESC, check_in DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function compterPourMois(string $userId, string $anneeMois, ?array $types = null): int
    {
        $sql    = "SELECT COUNT(*) as total FROM attendances WHERE user_id = ? AND date LIKE ?";
        $params = [$userId, $anneeMois . '%'];

        if ($types) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $sql .= " AND type IN ($placeholders)";
            $params = array_merge($params, $types);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function compterParTypeEtDate(string $date, array $types): int
    {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM attendances WHERE date = ? AND type IN ($placeholders)");
        $stmt->execute(array_merge([$date], $types));
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function dernierPointagesDuJour(string $date, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.nom, u.email, u.department
            FROM attendances a
            JOIN users u ON u.id = a.user_id
            WHERE a.date = ?
            ORDER BY a.check_in DESC
            LIMIT $limit
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function compterPourMoisGlobal(string $anneeMois, string $type): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM attendances WHERE date LIKE ? AND type = ?");
        $stmt->execute([$anneeMois . '%', $type]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function listePourMois(string $anneeMois, ?string $department = null): array
    {
        $sql    = "SELECT a.*, u.nom, u.email, u.department FROM attendances a JOIN users u ON u.id = a.user_id WHERE a.date LIKE ?";
        $params = [$anneeMois . '%'];

        if ($department) {
            $sql .= " AND u.department = ?";
            $params[] = $department;
        }

        $sql .= " ORDER BY u.department, u.nom, a.date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function presencesParDate(string $date, ?string $department = null, ?string $statut = null): array
    {
        $sql    = "SELECT a.*, u.nom, u.email, u.department FROM attendances a JOIN users u ON u.id = a.user_id WHERE a.date = ?";
        $params = [$date];

        if ($department) {
            $sql .= " AND u.department = ?";
            $params[] = $department;
        }

        if ($statut) {
            $sql .= " AND a.type = ?";
            $params[] = $statut;
        }

        $sql .= " ORDER BY a.check_in DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pointageOuvert(string $userId, string $date): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM attendances
            WHERE user_id = ? AND date = ? AND check_out IS NULL
            LIMIT 1
        ");
        $stmt->execute([$userId, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function creerArrivee(
        string $userId,
        string $type = 'present',
        ?string $date = null,
        ?string $heure = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $adresse = null,
        string $status = 'valide'
    ): int {
        $date  = $date ?? date('Y-m-d');
        $heure = $heure ?? date('H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO attendances (user_id, date, check_in, type, status, latitude, longitude, adresse)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $date, $heure, $type, $status, $latitude, $longitude, $adresse]);

        return (int) $this->db->lastInsertId();
    }

    public function marquerDepart(string $userId, ?string $date = null, ?string $heure = null): bool
    {
        $date  = $date ?? date('Y-m-d');
        $heure = $heure ?? date('H:i:s');

        $stmt = $this->db->prepare("UPDATE attendances SET check_out = ? WHERE user_id = ? AND date = ?");
        return $stmt->execute([$heure, $userId, $date]);
    }

    public function mettreAJourType(string $userId, string $date, string $type): bool
    {
        $stmt = $this->db->prepare("UPDATE attendances SET type = ? WHERE user_id = ? AND date = ?");
        return $stmt->execute([$type, $userId, $date]);
    }
}