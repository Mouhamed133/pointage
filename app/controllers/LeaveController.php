<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/User.php';

class LeaveController
{
    private PDO $db;
    private string $uploadDir;
    private Leave $leaveModel;
    private User $userModel;

    public function __construct()
    {
        $database        = new Database();
        $this->db        = $database->getConnection();
        $this->uploadDir = __DIR__ . '/../../public/uploads/justificatifs/';
        $this->leaveModel = new Leave();
        $this->userModel  = new User();
    }

    public function liste(): void
    {
        $statut = $_GET['statut'] ?? '';

        $conges = $this->leaveModel->liste($statut);

        $this->json(true, 'OK', $conges);
    }

    public function soumettre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $userId      = !empty($_POST['user_id']) ? trim($_POST['user_id']) : $_SESSION['user']['id'];
        $type        = trim($_POST['type']        ?? '');
        $startDate   = trim($_POST['start_date']  ?? '');
        $endDate     = trim($_POST['end_date']     ?? '');
        $reason      = trim($_POST['reason']       ?? '');
        $dateAbsence = trim($_POST['date_absence'] ?? $startDate);

        if (empty($type) || empty($startDate) || empty($endDate)) {
            $this->json(false, 'Tous les champs sont requis');
            return;
        }

        if ($startDate > $endDate) {
            $this->json(false, 'La date de fin doit etre apres la date de debut');
            return;
        }

        $documentPath = null;
        if (!empty($_FILES['document']['name'])) {
            $file     = $_FILES['document'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['pdf', 'jpg', 'jpeg', 'png'];

            if (!in_array($ext, $allowed)) {
                $this->json(false, 'Format non autorise. Utilisez PDF, JPG ou PNG');
                return;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                $this->json(false, 'Fichier trop volumineux (max 5MB)');
                return;
            }

            $filename     = 'justif_' . $userId . '_' . time() . '.' . $ext;
            $destPath     = $this->uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $this->json(false, 'Erreur lors de l\'upload du document');
                return;
            }

            $documentPath = $filename;
        }

        $leaveId = $this->leaveModel->creer($userId, $type, $startDate, $endDate, $reason, $documentPath, $dateAbsence);

        $etudiant    = $this->userModel->findById($userId);
        $nomEtudiant = $etudiant ? $etudiant['nom'] : $userId;

        $this->logAction('conge_soumettre', $nomEtudiant, $leaveId);
        $this->json(true, 'Demande soumise avec succes');
    }

    public function approuver(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
            $this->json(false, 'Acces refuse');
            return;
        }

        $id         = trim($_POST['id'] ?? '');
        $reviewerId = $_SESSION['user']['id'];

        if (empty($id)) { $this->json(false, 'ID manquant'); return; }

        $nomEtudiant = $this->leaveModel->getNomEtudiant((int) $id) ?? $id;

        $this->leaveModel->approuver((int) $id, $reviewerId);

        $this->logAction('conge_approuve', $nomEtudiant, $id);
        $this->json(true, 'Demande approuvee');
    }

    public function refuser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
            $this->json(false, 'Acces refuse');
            return;
        }

        $id         = trim($_POST['id'] ?? '');
        $reviewerId = $_SESSION['user']['id'];

        if (empty($id)) { $this->json(false, 'ID manquant'); return; }

        $nomEtudiant = $this->leaveModel->getNomEtudiant((int) $id) ?? $id;

        $this->leaveModel->refuser((int) $id, $reviewerId);

        $this->logAction('conge_refuse', $nomEtudiant, $id);
        $this->json(true, 'Demande refusee');
    }

    public function supprimer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $id     = trim($_POST['id'] ?? '');
        $userId = $_SESSION['user']['id'];
        $role   = $_SESSION['user']['role'];

        if (empty($id)) { $this->json(false, 'ID manquant'); return; }

        $document = $this->leaveModel->getDocument((int) $id);
        if ($document) {
            $filePath = $this->uploadDir . $document;
            if (file_exists($filePath)) unlink($filePath);
        }

        if ($role === 'etudiant') {
            $this->leaveModel->supprimerCommeEtudiant((int) $id, $userId);
        } else {
            $this->leaveModel->supprimerCommeAdmin((int) $id);
        }

        $this->logAction('conge_supprime', 'leaves', $id);
        $this->json(true, 'Demande supprimee');
    }

    private function logAction(string $action, string $entity = '', $entityId = ''): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt   = $this->db->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $entity, $entityId, $ip]);
        } catch (\Exception $e) {}
    }

    private function json(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }
}