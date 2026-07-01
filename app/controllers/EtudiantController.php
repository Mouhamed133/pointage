<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/QrCode.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Leave.php';

class EtudiantController
{
    private PDO $db;
    private QrCode $qrCodeModel;
    private Attendance $attendanceModel;
    private Leave $leaveModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->qrCodeModel     = new QrCode();
        $this->attendanceModel = new Attendance();
        $this->leaveModel      = new Leave();
    }

    // ============================================
    // DASHBOARD ETUDIANT
    // ============================================
    public function dashboard(): void
    {
        if (($_SESSION['user']['role'] ?? '') !== 'etudiant') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $today  = date('Y-m-d');
        $mois   = date('Y-m');

        $qr      = $this->qrCodeModel->parUtilisateur($userId);
        $qrToken = $qr ? $qr['token'] : null;

        $pointageAujourdhui = $this->attendanceModel->duJour($userId, $today);

        $totalJours = $this->attendanceModel->compterPourMois($userId, $mois);
        $presents   = $this->attendanceModel->compterPourMois($userId, $mois, ['present', 'retard']);
        $retards    = $this->attendanceModel->compterPourMois($userId, $mois, ['retard']);
        $absences   = $this->attendanceModel->compterPourMois($userId, $mois, ['absence']);

        $historique = $this->attendanceModel->historique($userId, 10);
        $conges     = $this->leaveModel->pourEtudiant($userId, 5);

        require __DIR__ . '/../Views/etudiant/dashboard.php';
    }

    // ============================================
    // DEMANDE DE CONGE (AJAX)
    // ============================================
    public function demanderConge(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $userId = $_SESSION['user']['id'];
        $type   = $_POST['type']       ?? '';
        $debut  = $_POST['start_date'] ?? '';
        $fin    = $_POST['end_date']   ?? '';
        $raison = $_POST['reason']     ?? '';

        if (empty($type) || empty($debut) || empty($fin)) {
            $this->json(false, 'Tous les champs sont requis');
            return;
        }

        $this->leaveModel->creer($userId, $type, $debut, $fin, $raison);

        // ✅ LOG
        $this->logAction('conge_soumettre', 'absences', $userId, $userId);

        $this->json(true, 'Demande de conge envoyee avec succes');
    }

    // ============================================
    // REGENERER MON QR CODE PERSONNEL (AJAX)
    // ============================================
    public function regenererQR(): void
    {
        if (($_SESSION['user']['role'] ?? '') !== 'etudiant') {
            $this->json(false, 'Acces refuse');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $userId = $_SESSION['user']['id'];
        $token  = $this->qrCodeModel->regenerer($userId, 16);

        // ✅ LOG
        $this->logAction('update', 'qr_codes', $userId, $userId);

        $this->json(true, 'QR Code regenere avec succes', ['token' => $token]);
    }

    // ============================================
    // UPLOAD PHOTO DE PROFIL (AJAX)
    // ============================================
    public function uploadPhoto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $userId = $_SESSION['user']['id'] ?? '';
        if (empty($userId)) {
            $this->json(false, 'Non connecte');
            return;
        }

        if (empty($_FILES['photo']['name'])) {
            $this->json(false, 'Aucun fichier selectionne');
            return;
        }

        $file    = $_FILES['photo'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $this->json(false, 'Format non supporte. Utilisez JPG, PNG ou WebP');
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(false, 'Image trop lourde (max 5MB)');
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(false, 'Erreur lors du transfert du fichier');
            return;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/photos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Supprimer ancienne photo
        $stmt = $this->db->prepare("SELECT photo FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['photo'])) {
            $oldFile = $uploadDir . $user['photo'];
            if (file_exists($oldFile)) unlink($oldFile);
        }

        $nomFichier  = $userId . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $nomFichier;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->json(false, 'Erreur lors de l\'enregistrement de la photo');
            return;
        }

        $stmt = $this->db->prepare("UPDATE users SET photo = ? WHERE id = ?");
        $stmt->execute([$nomFichier, $userId]);
        $_SESSION['user']['photo'] = $nomFichier;

        // ✅ LOG
        $this->logAction('update_profil', 'users', $userId, $userId);

        $this->json(true, 'Photo mise a jour avec succes', ['photo' => $nomFichier]);
    }

    // ============================================
    // CHANGER MOT DE PASSE (AJAX)
    // ============================================
    public function changerPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $userId = $_SESSION['user']['id'] ?? '';
        if (empty($userId)) {
            $this->json(false, 'Non connecte');
            return;
        }

        $pwdActuel  = $_POST['password_actuel']  ?? '';
        $pwdNouveau = $_POST['password_nouveau']  ?? '';

        if (empty($pwdActuel) || empty($pwdNouveau)) {
            $this->json(false, 'Tous les champs sont requis');
            return;
        }

        if (strlen($pwdNouveau) < 6) {
            $this->json(false, 'Le nouveau mot de passe doit avoir au moins 6 caracteres');
            return;
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($pwdActuel, $user['password_hash'])) {
            $this->json(false, 'Mot de passe actuel incorrect');
            return;
        }

        $hash = password_hash($pwdNouveau, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);

        // ✅ LOG
        $this->logAction('update_profil', 'users', $userId, $userId);

        $this->json(true, 'Mot de passe change avec succes');
    }

    // ============================================
    // HELPER JSON
    // ============================================
    private function json(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }

    // ============================================
    // LOG ACTION — identique à AuthController
    // user_id passé explicitement pour éviter les NULL
    // ============================================
    private function logAction(
        string $action,
        string $entity      = '',
        string $entityId    = '',
        string $forceUserId = ''
    ): void {
        try {
            $userId = !empty($forceUserId)
                ? $forceUserId
                : ($_SESSION['user']['id'] ?? null);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, entity, entity_id, ip)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $action, $entity, $entityId ?: null, $ip]);
        } catch (\Exception $e) {}
    }
}