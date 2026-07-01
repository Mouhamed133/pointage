<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/QrCode.php';
require_once __DIR__ . '/../models/User.php';

class AttendanceController
{
    private PDO $db;
    private Attendance $attendanceModel;
    private QrCode $qrCodeModel;
    private User $userModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->attendanceModel = new Attendance();
        $this->qrCodeModel     = new QrCode();
        $this->userModel       = new User();
    }

    // ============================================
    // LISTE DES PRESENCES (AJAX)
    // ============================================
    public function liste(): void
    {
        $date   = $_GET['date']   ?? date('Y-m-d');
        $dept   = $_GET['dept']   ?? '';
        $statut = $_GET['statut'] ?? '';

        $presences = $this->attendanceModel->presencesParDate($date, $dept ?: null, $statut ?: null);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $presences]);
        exit;
    }

    // ============================================
    // LISTE DES PRESENCES (vue)
    // ============================================
    public function index(): void
    {
        $date      = $_GET['date'] ?? date('Y-m-d');
        $presences = $this->attendanceModel->presencesParDate($date);
        require_once __DIR__ . '/../Views/attendance/index.php';
    }

    // ============================================
    // POINTAGE AUTOMATIQUE (Arrivée OU Départ)
    // ============================================
    public function pointerAuto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        $token     = trim($_POST['token'] ?? '');
        $latitude  = 14.679620;
        $longitude = -17.441229;
        $adresse   = 'Etablissement scolaire — Dakar';
        $today       = date('Y-m-d');
        $heure       = date('H:i:s');
        $heureLimite = '08:30:00';

        $etudiant = $this->trouverEtudiantParToken($token);
        if (!$etudiant) {
            $this->jsonResponse(false, 'QR Code invalide ou etudiant inactif.');
            return;
        }

        $pointageOuvert = $this->attendanceModel->pointageOuvert($etudiant['id'], $today);

        // Cas 1 : déjà arrivé, pas encore parti → départ
        if ($pointageOuvert) {
            $this->attendanceModel->marquerDepart($etudiant['id'], $today, $heure);

            // ✅ LOG avec l'ID de l'ÉTUDIANT (pas l'admin qui scanne)
            $this->logAction('checkout', $etudiant['nom'] ?? $etudiant['email'], $etudiant['id']);

            $this->jsonResponse(true, 'Depart enregistre avec succes.', [
                'etudiant' => $etudiant['nom'] ?? $etudiant['email'],
                'heure'    => $heure,
                'mode'     => 'depart',
                'type'     => 'present',
            ]);
            return;
        }

        // Cas 2 : déjà arrivé ET parti → rien
        if ($this->attendanceModel->existePourDate($etudiant['id'], $today)) {
            $this->jsonResponse(false, 'Cet etudiant a deja pointe arrivee et depart aujourd\'hui.');
            return;
        }

        // Cas 3 : pas encore pointé → arrivée
        $type = ($heure > $heureLimite) ? 'retard' : 'present';
        $this->attendanceModel->creerArrivee(
            $etudiant['id'], $type, $today, $heure,
            $latitude, $longitude, $adresse, 'valide'
        );

        // ✅ LOG avec l'ID de l'ÉTUDIANT
        $this->logAction('checkin', $etudiant['nom'] ?? $etudiant['email'], $etudiant['id']);

        $this->jsonResponse(true, 'Arrivee enregistree avec succes.', [
            'etudiant'  => $etudiant['nom'] ?? $etudiant['email'],
            'heure'     => $heure,
            'type'      => $type,
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'mode'      => 'arrivee',
        ]);
    }

    // ============================================
    // CHECK-IN (Arrivée)
    // ============================================
    public function checkin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        $token     = trim($_POST['token'] ?? '');
        $latitude  = 14.679620;
        $longitude = -17.441229;
        $adresse   = 'Etablissement scolaire — Dakar';
        $today       = date('Y-m-d');
        $heure       = date('H:i:s');
        $heureLimite = '08:30:00';

        $etudiant = $this->trouverEtudiantParToken($token);
        if (!$etudiant) {
            $this->jsonResponse(false, 'QR Code invalide ou etudiant inactif.');
            return;
        }

        if ($this->attendanceModel->existePourDate($etudiant['id'], $today)) {
            $this->jsonResponse(false, 'Arrivee deja enregistree pour aujourd\'hui.');
            return;
        }

        $type = ($heure > $heureLimite) ? 'retard' : 'present';
        $this->attendanceModel->creerArrivee(
            $etudiant['id'], $type, $today, $heure,
            $latitude, $longitude, $adresse, 'valide'
        );

        // ✅ LOG avec l'ID de l'ÉTUDIANT
        $this->logAction('checkin', $etudiant['nom'] ?? $etudiant['email'], $etudiant['id']);

        $this->jsonResponse(true, 'Arrivee enregistree avec succes.', [
            'etudiant'  => $etudiant['nom'] ?? $etudiant['email'],
            'heure'     => $heure,
            'type'      => $type,
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ]);
    }

    // ============================================
    // CHECK-OUT (Départ)
    // ============================================
    public function checkout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        $token = trim($_POST['token'] ?? '');
        $today = date('Y-m-d');
        $heure = date('H:i:s');

        $etudiant = $this->trouverEtudiantParToken($token);
        if (!$etudiant) {
            $this->jsonResponse(false, 'QR Code invalide ou etudiant inactif.');
            return;
        }

        $pointage = $this->attendanceModel->pointageOuvert($etudiant['id'], $today);
        if (!$pointage) {
            $this->jsonResponse(false, 'Aucune arrivee enregistree aujourd\'hui.');
            return;
        }

        $this->attendanceModel->marquerDepart($etudiant['id'], $today, $heure);

        // ✅ LOG avec l'ID de l'ÉTUDIANT
        $this->logAction('checkout', $etudiant['nom'] ?? $etudiant['email'], $etudiant['id']);

        $this->jsonResponse(true, 'Depart enregistre avec succes.', [
            'etudiant' => $etudiant['nom'] ?? $etudiant['email'],
            'heure'    => $heure,
            'mode'     => 'depart',
        ]);
    }

    // ============================================
    // SCANNER QR CODE (vue)
    // ============================================
    public function scanner(): void
    {
        require_once __DIR__ . '/../Views/attendance/scanner.php';
    }

    // ============================================
    // GÉNÉRER QR CODE
    // ============================================
    public function genererQR(): void
    {
        $userId = $_GET['user_id'] ?? '';
        if (empty($userId)) {
            header('Location: index.php?route=presences');
            exit;
        }
        $token = $this->qrCodeModel->genererSiAbsent($userId, 4);
        $this->jsonResponse(true, 'QR Code genere.', ['token' => $token]);
    }

    // ============================================
    // HELPER — retrouve un étudiant actif via token QR
    // ============================================
    private function trouverEtudiantParToken(string $token): ?array
    {
        $qr = $this->qrCodeModel->trouverTokenValide($token);
        if (!$qr) return null;
        return $this->userModel->findById($qr['user_id']);
    }

    // ============================================
    // HELPERS
    // ============================================
    private function jsonResponse(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }

    // ✅ CORRECTION CLÉE :
    // entity     = nom de l'étudiant (affiché dans l'audit)
    // entityId   = UUID de l'étudiant (pour le JOIN dans auditListe)
    // forceUserId = UUID de l'étudiant (user_id dans audit_logs)
    //
    // Avant : user_id = admin connecté → audit ne montrait que l'admin
    // Après : user_id = étudiant concerné → audit montre l'étudiant
    private function logAction(
        string $action,
        string $entity     = '',
        string $forceUserId = ''
    ): void {
        try {
            // Pour checkin/checkout : on logue l'étudiant, pas l'admin qui scanne
            $userId = !empty($forceUserId)
                ? $forceUserId
                : ($_SESSION['user']['id'] ?? null);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs (user_id, action, entity, entity_id, ip)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $action, $entity, null, $ip]);
        } catch (\Exception $e) {}
    }
}