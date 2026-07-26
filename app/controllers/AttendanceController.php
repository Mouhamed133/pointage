<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/QrCode.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/EmploiDuTemps.php';

class AttendanceController
{
    private PDO $db;
    private Attendance $attendanceModel;
    private QrCode $qrCodeModel;
    private User $userModel;
    private EmploiDuTemps $emploiModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->attendanceModel = new Attendance();
        $this->qrCodeModel     = new QrCode();
        $this->userModel       = new User();
        $this->emploiModel     = new EmploiDuTemps();
    }

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

    public function index(): void
    {
        $date      = $_GET['date'] ?? date('Y-m-d');
        $presences = $this->attendanceModel->presencesParDate($date);
        require_once __DIR__ . '/../Views/attendance/index.php';
    }

    // ============================================
    // POINTAGE AUTOMATIQUE — avec vérification emploi du temps
    // ============================================
    public function pointerAuto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        try {
            $token = trim($_POST['token'] ?? '');

            // 🔄 RÉCUPÉRATION DYNAMIQUE DE LA CONFIGURATION GPS
            $stmtConfig = $this->db->prepare("SELECT latitude, longitude FROM school_qr LIMIT 1");
            $stmtConfig->execute();
            $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);

            $latitude  = $config && !empty($config['latitude'])  ? (float)$config['latitude']  : 14.721360;
            $longitude = $config && !empty($config['longitude']) ? (float)$config['longitude'] : -17.463802;

            $adresse   = 'Etablissement scolaire — Dakar';
            $today     = date('Y-m-d');
            $heure     = date('H:i:s');

            $etudiant = $this->trouverEtudiantParToken($token);
            if (!$etudiant) {
                $this->jsonResponse(false, 'QR Code invalide ou etudiant inactif.');
                return;
            }

            // ✅ VÉRIFICATION EMPLOI DU TEMPS
            $cohorteId = isset($etudiant['cohorte_id']) ? (int)$etudiant['cohorte_id'] : 0;
            if ($cohorteId <= 0) {
                $this->jsonResponse(false, 'Cohorte introuvable pour cet etudiant.');
                return;
            }

            $verification = $this->emploiModel->verifierPointage($cohorteId);

            if ($verification['statut'] !== 'ok') {
                $this->jsonResponse(false, $verification['message'], [
                    'statut'  => $verification['statut'],
                    'creneau' => $verification['creneau'],
                ]);
                return;
            }

            $pointageOuvert = $this->attendanceModel->pointageOuvert($etudiant['id'], $today);

            // Cas 1 : déjà arrivé → départ
            if ($pointageOuvert) {
                $this->attendanceModel->marquerDepart($etudiant['id'], $today, $heure);
                $this->logAction('checkout', $etudiant['nom'] ?? $etudiant['email'], $etudiant['id']);
                $this->jsonResponse(true, 'Depart enregistre avec succes.', [
                    'etudiant' => $etudiant['nom'] ?? $etudiant['email'],
                    'heure'    => $heure,
                    'mode'     => 'depart',
                    'type'     => 'present',
                ]);
                return;
            }

            // Cas 2 : déjà complet
            if ($this->attendanceModel->existePourDate($etudiant['id'], $today)) {
                $this->jsonResponse(false, 'Cet etudiant a deja pointe arrivee et depart aujourd\'hui.');
                return;
            }

            // Cas 3 : arrivée — type selon emploi du temps
            $type = 'present';
            if (!empty($verification['creneau']['heure_debut'])) {
                $type = ($heure > $verification['creneau']['heure_debut']) ? 'retard' : 'present';
            }

            $this->attendanceModel->creerArrivee(
                $etudiant['id'],
                $type,
                $today,
                $heure,
                $latitude,
                $longitude,
                $adresse,
                'valide'
            );
            $this->logAction('checkin', $etudiant['nom'] ?? $etudiant['email'], $etudiant['id']);

            $this->jsonResponse(true, 'Arrivee enregistree avec succes.', [
                'etudiant'  => $etudiant['nom'] ?? $etudiant['email'],
                'heure'     => $heure,
                'type'      => $type,
                'latitude'  => $latitude,
                'longitude' => $longitude,
                'mode'      => 'arrivee',
            ]);
        } catch (Throwable $e) {
            error_log('Erreur pointerAuto: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            http_response_code(500);
            $this->jsonResponse(false, 'Erreur serveur lors du pointage: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }
    }

    // ============================================
    // CHECK-IN (Arrivée) — avec vérification emploi du temps
    // ============================================
    public function checkin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        $token = trim($_POST['token'] ?? '');

        // 🔄 RÉCUPÉRATION DYNAMIQUE DE LA CONFIGURATION GPS
        $stmtConfig = $this->db->prepare("SELECT latitude, longitude FROM school_qr LIMIT 1");
        $stmtConfig->execute();
        $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);

        $latitude  = $config && !empty($config['latitude'])  ? (float)$config['latitude']  : 14.721360;
        $longitude = $config && !empty($config['longitude']) ? (float)$config['longitude'] : -17.463802;

        $adresse   = 'Etablissement scolaire — Dakar';
        $today     = date('Y-m-d');
        $heure     = date('H:i:s');

        $etudiant = $this->trouverEtudiantParToken($token);
        if (!$etudiant) {
            $this->jsonResponse(false, 'QR Code invalide ou etudiant inactif.');
            return;
        }

        // ✅ VÉRIFICATION EMPLOI DU TEMPS
        // ✅ VÉRIFICATION EMPLOI DU TEMPS
        $cohorteId    = $etudiant['cohorte_id'] ?? 0;
        $verification = $this->emploiModel->verifierPointage((int) $cohorteId);
        if ($verification['statut'] !== 'ok') {
            $this->jsonResponse(false, $verification['message'], [
                'statut'  => $verification['statut'],
                'creneau' => $verification['creneau'],
            ]);
            return;
        }

        if ($this->attendanceModel->existePourDate($etudiant['id'], $today)) {
            $this->jsonResponse(false, 'Arrivee deja enregistree pour aujourd\'hui.');
            return;
        }

        $type = 'present';
        if (!empty($verification['creneau']['heure_debut'])) {
            $type = ($heure > $verification['creneau']['heure_debut']) ? 'retard' : 'present';
        }
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
        $this->logAction('checkout', $etudiant['nom'] ?? $etudiant['email'], $etudiant['id']);

        $this->jsonResponse(true, 'Depart enregistre avec succes.', [
            'etudiant' => $etudiant['nom'] ?? $etudiant['email'],
            'heure'    => $heure,
            'mode'     => 'depart',
        ]);
    }

    public function scanner(): void
    {
        require_once __DIR__ . '/../Views/attendance/scanner.php';
    }

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

    private function trouverEtudiantParToken(string $token): ?array
    {
        $qr = $this->qrCodeModel->trouverTokenValide($token);
        if (!$qr) return null;
        return $this->userModel->findById($qr['user_id']);
    }

    private function jsonResponse(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }

    private function logAction(string $action, string $entity = '', string $forceUserId = ''): void
    {
        try {
            $userId = !empty($forceUserId) ? $forceUserId : ($_SESSION['user']['id'] ?? null);
            $ip     = $this->getIp();
            $stmt   = $this->db->prepare(
                "INSERT INTO audit_logs (user_id, action, entity, entity_id, ip) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $action, $entity, null, $ip]);
        } catch (\Exception $e) {
        }
    }

    private function getIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return $ip;
            }
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return ($ip === '::1' || $ip === '127.0.0.1') ? '127.0.0.1 (local)' : $ip;
    }
}
