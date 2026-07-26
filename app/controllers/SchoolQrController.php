<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/QrCode.php';

class SchoolQrController
{
    private PDO $db;
    private QrCode $qrCodeModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->qrCodeModel = new QrCode();
    }

    // ============================================
    // GET OR CREATE — retourne le QR actif
    // ============================================
    public function getOrCreate(): void
    {
        $stmt = $this->db->query("SELECT * FROM school_qr WHERE is_active = 1 LIMIT 1");
        $qr   = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$qr) {
            $token = 'SCHOOL-' . strtoupper(bin2hex(random_bytes(8)));
            $stmt  = $this->db->prepare("INSERT INTO school_qr (token, label, latitude, longitude, rayon) VALUES (?, 'Entree principale', 14.6796200, -17.4412290, 500)");
            $stmt->execute([$token]);
            $qr = ['token' => $token, 'label' => 'Entree principale', 'latitude' => 14.6796200, 'longitude' => -17.4412290, 'rayon' => 500];
        }

        $qr['url'] = $this->urlBase() . '/index.php?route=scan&token=' . urlencode($qr['token']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $qr]);
        exit;
    }

    // ============================================
    // SCAN REDIRECT — point d'entrée après scan QR mural
    // ============================================
    public function scanRedirect(): void
    {
        $token = trim($_GET['token'] ?? '');

        if (empty($token) || strpos($token, 'SCHOOL-') !== 0) {
            header('Location: index.php?route=login&scan_erreur=token_invalide');
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM school_qr WHERE token = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$token]);
        $schoolQr = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schoolQr) {
            header('Location: index.php?route=login&scan_erreur=qr_expire');
            exit;
        }

        $_SESSION['scan_pending_token'] = $token;

        if (isset($_SESSION['user'])) {
            $this->effectuerPointageEtRediriger();
            return;
        }

        header('Location: index.php?route=login&scan=1');
        exit;
    }

    // ============================================
    // POINTER APRÈS SCAN — appelé par AuthController
    // ============================================
    public function pointerApresScan(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?route=login');
            exit;
        }

        $token = $_SESSION['scan_pending_token'] ?? '';
        if (empty($token)) {
            $this->redirigerDashboard();
            return;
        }

        $this->effectuerPointageEtRediriger();
    }

    // ============================================
    // POINTER VIA AJAX (scanner intégré dans l'app)
    // ============================================
    public function pointer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Methode non autorisee']);
            exit;
        }

        $tokenScanne = trim($_POST['token_ecole'] ?? '');

        require_once __DIR__ . '/AttendanceController.php';

        if (strpos($tokenScanne, 'SCHOOL-') === 0) {
            $stmt = $this->db->prepare("SELECT * FROM school_qr WHERE token = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$tokenScanne]);
            $schoolQr = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schoolQr) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "QR Code de l'ecole invalide ou expire"]);
                exit;
            }

            $userId = $_SESSION['user']['id'];
            $qrEtu  = $this->qrCodeModel->parUtilisateur($userId);

            if (!$qrEtu) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas de QR Code."]);
                exit;
            }

            $_POST['token'] = $qrEtu['token'];
        } else {
            $qrEtu = $this->qrCodeModel->trouverTokenValide($tokenScanne);
            if (!$qrEtu) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'QR Code invalide ou etudiant inactif']);
                exit;
            }
            $_POST['token'] = $tokenScanne;
        }

        $attendance = new AttendanceController();
        $attendance->pointerAuto();
    }

    // ============================================
    // LIRE LA CONFIG GPS
    // ============================================
    public function getConfig(): void
    {
        $stmt   = $this->db->query("SELECT latitude, longitude, rayon, label FROM school_qr WHERE is_active = 1 LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$config) {
            $config = ['latitude' => 14.721736, 'longitude' => -17.463802, 'rayon' => 10, 'label' => 'Entree principale'];
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $config]);
        exit;
    }

    // ============================================
    // METTRE À JOUR LA CONFIG GPS (ADMIN)
    // ============================================
    public function updateConfig(): void
    {
        if ($_SESSION['user']['role'] !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acces refuse']);
            exit;
        }

        $latitude  = floatval($_POST['latitude']  ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        $rayon     = intval($_POST['rayon']       ?? 500);

        if ($latitude == 0 || $longitude == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Coordonnees invalides']);
            exit;
        }

        $stmt = $this->db->prepare("UPDATE school_qr SET latitude = ?, longitude = ?, rayon = ? WHERE is_active = 1");
        $stmt->execute([$latitude, $longitude, $rayon]);

        if ($stmt->rowCount() === 0) {
            $token = 'SCHOOL-' . strtoupper(bin2hex(random_bytes(8)));
            $stmt2 = $this->db->prepare("
                INSERT INTO school_qr (token, label, latitude, longitude, rayon, is_active)
                VALUES (?, 'Entree principale', ?, ?, ?, 1)
            ");
            $stmt2->execute([$token, $latitude, $longitude, $rayon]);
        }

        // LOG admin qui modifie la config GPS
        $this->logAction('update_gps', 'school_qr', $_SESSION['user']['id'] ?? '');

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Configuration GPS mise a jour !']);
        exit;
    }

    // ============================================
    // RÉGÉNÉRER LE QR CODE ÉCOLE
    // ============================================
    public function regenerer(): void
    {
        if ($_SESSION['user']['role'] !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acces refuse']);
            exit;
        }

        $this->db->exec("UPDATE school_qr SET is_active = 0");
        $token = 'SCHOOL-' . strtoupper(bin2hex(random_bytes(8)));
        $label = trim($_POST['label'] ?? 'Entree principale');

        $stmt = $this->db->prepare("
            INSERT INTO school_qr (token, label, latitude, longitude, rayon)
            SELECT ?, ?, latitude, longitude, rayon FROM school_qr ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$token, $label]);

        $url = $this->urlBase() . '/index.php?route=scan&token=' . urlencode($token);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'QR Code regenere',
            'data'    => ['token' => $token, 'label' => $label, 'url' => $url]
        ]);
        exit;
    }

    // ============================================
    // PRIVÉ — effectue le pointage via les models
    // ============================================
    private function effectuerPointageEtRediriger(): void
    {
        unset($_SESSION['scan_pending_token']);

        require_once __DIR__ . '/../models/Attendance.php';

        $userId          = $_SESSION['user']['id'];
        $nomEtudiant     = $_SESSION['user']['nom'] ?? '';
        $qrEtu           = $this->qrCodeModel->parUtilisateur($userId);

        if (!$qrEtu) {
            $this->redirigerDashboard('Vous n\'avez pas de QR Code. Contactez l\'administrateur.', 'erreur');
            return;
        }

        $attendanceModel = new Attendance();
        $today           = date('Y-m-d');
        $heure           = date('H:i:s');
        $heureLimite     = '08:30:00';

        // Cas 1 : déjà arrivé → départ
        $pointageOuvert = $attendanceModel->pointageOuvert($userId, $today);
        if ($pointageOuvert) {
            $attendanceModel->marquerDepart($userId, $today, $heure);
            // ✅ user_id = étudiant lui-même
            $this->logAction('checkout', $nomEtudiant, $userId);
            $this->redirigerDashboard('Depart enregistre a ' . substr($heure, 0, 5), 'scan_ok');
            return;
        }

        // Cas 2 : déjà complet
        if ($attendanceModel->existePourDate($userId, $today)) {
            $this->redirigerDashboard('Vous avez deja pointe arrivee et depart aujourd\'hui.', 'scan_info');
            return;
        }

        // Cas 3 : arrivée
        $type = ($heure > $heureLimite) ? 'retard' : 'present';
        $attendanceModel->creerArrivee(
            $userId, $type, $today, $heure,
            14.679620, -17.441229,
            'Etablissement scolaire — Dakar', 'valide'
        );
        // ✅ user_id = étudiant lui-même
        $this->logAction('checkin', $nomEtudiant, $userId);
        $this->redirigerDashboard('Arrivee enregistree a ' . substr($heure, 0, 5), 'scan_ok');
    }

    private function redirigerDashboard(string $message = '', string $type = ''): void
    {
        $role   = $_SESSION['user']['role'] ?? 'etudiant';
        $base   = ($role === 'etudiant') ? 'etudiant/dashboard' : 'dashboard';
        $params = '';
        if (!empty($message)) {
            $params = '&scan_msg=' . urlencode($message) . '&scan_type=' . urlencode($type);
        }
        header('Location: index.php?route=' . $base . $params);
        exit;
    }

    private function urlBase(): string
    {
        $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $hote      = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $chemin    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/pointagepro/public')), '/');
        return $protocole . '://' . $hote . $chemin;
    }

    // ✅ CORRECTION CLÉE :
    // forceUserId = UUID de l'étudiant qui a pointé
    // (pas l'admin connecté qui scanne)
    private function logAction(
        string $action,
        string $entity      = '',
        string $forceUserId = ''
    ): void {
        try {
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