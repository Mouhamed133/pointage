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
        $this->qrCodeModel = new QrCode();
        $this->userModel = new User();
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
        $date = $_GET['date'] ?? date('Y-m-d');

        $presences = $this->attendanceModel->presencesParDate($date);

        require_once __DIR__ . '/../Views/attendance/index.php';
    }

    // ============================================
    // POINTAGE AUTOMATIQUE (Arrivee OU Depart, detecte seul)
    // Pas de mode a choisir : on regarde l'etat reel de l'etudiant
    // pour aujourd'hui et on en deduit l'action a effectuer.
    //   - pas encore pointe aujourd'hui      -> arrivee
    //   - arrive mais pas encore parti       -> depart
    //   - arrive ET parti deja aujourd'hui   -> refuse (rien a faire)
    // ============================================
    public function pointerAuto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        $token     = trim($_POST['token']     ?? '');
        // Position fixe de l'etablissement — enregistree a l'arrivee
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

        // Cas 1 : deja arrive, pas encore parti -> on enregistre le depart
        if ($pointageOuvert) {
            $this->attendanceModel->marquerDepart($etudiant['id'], $today, $heure);

            $this->logAction('checkout', $etudiant['nom'] ?? $etudiant['email'], (string) $etudiant['id']);

            $this->jsonResponse(true, 'Depart enregistre avec succes.', [
                'etudiant' => $etudiant['nom'] ?? $etudiant['email'],
                'heure'    => $heure,
                'mode'     => 'depart',
            ]);
            return;
        }

        // Cas 2 : un pointage existe deja (arrivee + depart deja faits) -> rien a refaire
        if ($this->attendanceModel->existePourDate($etudiant['id'], $today)) {
            $this->jsonResponse(false, 'Cet etudiant a deja pointe arrivee et depart aujourd\'hui.');
            return;
        }

        // Cas 3 : aucun pointage aujourd'hui -> on enregistre l'arrivee
        $type = ($heure > $heureLimite) ? 'retard' : 'present';

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

        $this->logAction('checkin', $etudiant['nom'] ?? $etudiant['email'], (string) $etudiant['id']);

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
    // CHECK-IN (Arrivée) — avec géolocalisation
    // Conserve pour le flux "QR Code Ecole" (SchoolQrController),
    // qui envoie explicitement le mode. La page de scan admin
    // utilise desormais pointerAuto() ci-dessus.
    // ============================================
    public function checkin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        $token     = trim($_POST['token']     ?? '');
        // Position fixe de l'etablissement — enregistree a chaque pointage
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
            $etudiant['id'],
            $type,
            $today,
            $heure,
            $latitude,
            $longitude,
            $adresse,
            'valide'
        );

        $this->logAction('checkin', $etudiant['nom'] ?? $etudiant['email'], (string) $etudiant['id']);

        $this->jsonResponse(true, 'Arrivee enregistree avec succes.', [
            'etudiant'  => $etudiant['nom'] ?? $etudiant['email'],
            'heure'     => $heure,
            'type'      => $type,
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ]);
    }

    // ============================================
    // CHECK-OUT (Départ) — avec géolocalisation
    // ============================================
    public function checkout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=presences');
            exit;
        }

        $token = trim($_POST['token'] ?? '');
        // latitude/longitude reçus mais non utilises (on ne remplace pas la geoloc d'arrivee)

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

        // On ne remplace PAS la géoloc d'arrivée — on met juste check_out
        $this->attendanceModel->marquerDepart($etudiant['id'], $today, $heure);

        $this->logAction('checkout', $etudiant['nom'] ?? $etudiant['email'], (string) $etudiant['id']);

        $this->jsonResponse(true, 'Depart enregistre avec succes.', [
            'etudiant' => $etudiant['nom'] ?? $etudiant['email'],
            'heure'    => $heure,
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
    // HELPER : retrouve un etudiant actif a partir d'un token QR
    // (orchestre QrCode::trouverTokenValide + User::findById)
    // ============================================
    private function trouverEtudiantParToken(string $token): ?array
    {
        $qr = $this->qrCodeModel->trouverTokenValide($token);

        if (!$qr) {
            return null;
        }

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

    private function logAction(string $action, string $entity = '', string $entityId = ''): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt   = $this->db->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $entity, $entityId, $ip]);
        } catch (\Exception $e) {}
    }
}