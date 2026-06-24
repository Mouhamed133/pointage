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
        $this->qrCodeModel = new QrCode();
        $this->attendanceModel = new Attendance();
        $this->leaveModel = new Leave();
    }

    // ============================================
    // DASHBOARD ETUDIANT
    // ============================================
    public function dashboard(): void
    {
        // Garde de role : seul un etudiant peut voir son propre dashboard.
        // Empeche un admin/manager connecte d'y acceder en tapant l'URL.
        if (($_SESSION['user']['role'] ?? '') !== 'etudiant') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $today  = date('Y-m-d');
        $mois   = date('Y-m');

        // Recuperer le token QR
        $qr = $this->qrCodeModel->parUtilisateur($userId);
        $qrToken = $qr ? $qr['token'] : null;

        // Pointage du jour
        $pointageAujourdhui = $this->attendanceModel->duJour($userId, $today);

        // Stats du mois
        $totalJours = $this->attendanceModel->compterPourMois($userId, $mois);

        // Present = present + retard
        $presents = $this->attendanceModel->compterPourMois($userId, $mois, ['present', 'retard']);
        $retards  = $this->attendanceModel->compterPourMois($userId, $mois, ['retard']);
        $absences = $this->attendanceModel->compterPourMois($userId, $mois, ['absence']);

        // Historique des 10 derniers pointages
        $historique = $this->attendanceModel->historique($userId, 10);

        // Demandes de conge
        $conges = $this->leaveModel->pourEtudiant($userId, 5);

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

        $this->json(true, 'Demande de conge envoyee avec succes');
    }

    // ============================================
    // REGENERER MON QR CODE PERSONNEL (AJAX)
    // L'ancien token devient invalide immediatement.
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

        $this->json(true, 'QR Code regenere avec succes', ['token' => $token]);
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
}