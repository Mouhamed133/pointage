<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/QrCode.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController
{
    private PDO $db;
    private User $userModel;
    private Attendance $attendanceModel;
    private QrCode $qrCodeModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->userModel = new User();
        $this->attendanceModel = new Attendance();
        $this->qrCodeModel = new QrCode();
    }

    public function login(): void
    {
        $erreur   = '';
        $scanMode = isset($_GET['scan']) && $_GET['scan'] === '1';
        $scanErreur = $_GET['scan_erreur'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email']    ?? '');
            $password = $_POST['password']      ?? '';

            if (empty($email) || empty($password)) {
                $erreur = 'Veuillez remplir tous les champs.';
            } else {
                $user = $this->userModel->findByEmail($email);
                if (!$user) {
                    $erreur = 'Email ou mot de passe incorrect.';
                } elseif (!$user['is_active']) {
                    $erreur = 'Votre compte n\'est pas encore active. Verifiez votre boite mail pour le lien d\'activation.';
                } elseif (!password_verify($password, $user['password_hash'])) {
                    $erreur = 'Email ou mot de passe incorrect.';
                } else {
                    $_SESSION['user'] = [
                        'id'         => $user['id'],
                        'nom'        => $user['nom'],
                        'email'      => $user['email'],
                        'role'       => $user['role'],
                        'department' => $user['department'],
                    ];
                    $this->logAction('login', 'users', $user['id']);

                    // -------------------------------------------------------
                    // SCAN EN ATTENTE : si l'etudiant arrive via un scan QR
                    // mural, on le redirige vers scan/pointer qui effectuera
                    // le pointage automatiquement avant d'aller au dashboard.
                    // -------------------------------------------------------
                    if (!empty($_SESSION['scan_pending_token'])) {
                        header('Location: index.php?route=scan/pointer');
                        exit;
                    }

                    // Redirection normale
                    if ($user['role'] === 'etudiant') {
                        header('Location: index.php?route=etudiant/dashboard');
                    } else {
                        header('Location: index.php?route=dashboard');
                    }
                    exit;
                }
            }
        }

        require __DIR__ . '/../Views/auth/login.php';
    }

    // ============================================
    // INSCRIPTION LIBRE DESACTIVEE
    // Seul un admin/manager peut creer un compte etudiant
    // ============================================
    public function register(): void
    {
        header('Location: index.php?route=login&inscription_desactivee=1');
        exit;
    }

    public function logout(): void
    {
        $this->logAction('logout', 'users', $_SESSION['user']['id'] ?? '');
        session_destroy();
        header('Location: index.php?route=login');
        exit;
    }

    public function dashboard(): void
    {
        $today = date('Y-m-d');

        $totalEtudiants = $this->userModel->compterEtudiantsActifs();

        // Present = present + retard
        $presents = $this->attendanceModel->compterParTypeEtDate($today, ['present', 'retard']);
        $retards  = $this->attendanceModel->compterParTypeEtDate($today, ['retard']);

        // Absents = total - presents
        $absents = max(0, $totalEtudiants - $presents);

        $derniersPointages = $this->attendanceModel->dernierPointagesDuJour($today, 10);

        require __DIR__ . '/../Views/dashboard/index.php';
    }

    public function stats(): void
    {
        $today = date('Y-m-d');

        $totalEtudiants = $this->userModel->compterEtudiantsActifs();

        $presents = $this->attendanceModel->compterParTypeEtDate($today, ['present', 'retard']);
        $retards  = $this->attendanceModel->compterParTypeEtDate($today, ['retard']);
        $absents  = max(0, $totalEtudiants - $presents);

        header('Content-Type: application/json');
        echo json_encode([
            'success'        => true,
            'presents'       => $presents,
            'absents'        => $absents,
            'retards'        => $retards,
            'totalEtudiants' => $totalEtudiants,
        ]);
        exit;
    }

    // ============================================
    // AUDIT LOG (AJAX)
    // ============================================
    public function auditListe(): void
    {
        $page   = intval($_GET['page']   ?? 1);
        $search = trim($_GET['search']   ?? '');
        $action = trim($_GET['action']   ?? '');
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $sql    = "SELECT a.*, u.nom, u.email, u.role FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.nom LIKE ? OR u.email LIKE ? OR a.action LIKE ? OR a.entity LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($action)) {
            $sql .= " AND a.action = ?";
            $params[] = $action;
        }

        $stmtCount = $this->db->prepare("SELECT COUNT(*) as total FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE 1=1" . ((!empty($search)) ? " AND (u.nom LIKE ? OR u.email LIKE ? OR a.action LIKE ? OR a.entity LIKE ?)" : "") . ((!empty($action)) ? " AND a.action = ?" : ""));
        $stmtCount->execute($params);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        $sql .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtActions = $this->db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
        $actions = $stmtActions->fetchAll(PDO::FETCH_COLUMN);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data'    => $logs,
            'total'   => intval($total),
            'page'    => $page,
            'pages'   => ceil($total / $limit),
            'actions' => $actions,
        ]);
        exit;
    }

    // MOT DE PASSE OUBLIE
    public function forgot(): void
    {
        $erreur = '';
        $succes = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $erreur = 'Veuillez entrer votre email.';
            } else {
                $user = $this->userModel->findByEmail($email);
                if (!$user || !$user['is_active']) {
                    $erreur = 'Aucun compte actif trouve avec cet email.';
                } else {
                    $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt->execute([$email]);
                    $token = bin2hex(random_bytes(32));
                    $stmt  = $this->db->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
                    $stmt->execute([$email, $token]);
                    $lien    = $this->urlBase() . '/index.php?route=reset&token=' . $token;
                    $envoye  = $this->envoyerEmailReset($email, $lien);
                    if ($envoye) {
                        $succes = 'Email envoye a ' . htmlspecialchars($email) . ' ! Verifiez votre boite mail.';
                    } else {
                        $erreur = 'Erreur envoi email. Verifiez la configuration SMTP.';
                    }
                }
            }
        }
        require __DIR__ . '/../Views/auth/forgot.php';
    }

    // RESET MOT DE PASSE
    public function reset(): void
    {
        $erreur = '';
        $token  = trim($_GET['token'] ?? '');

        if (empty($token)) {
            header('Location: index.php?route=login');
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            $erreur = 'Ce lien est invalide ou a expire. Faites une nouvelle demande.';
            require __DIR__ . '/../Views/auth/forgot.php';
            return;
        }

        $email = $reset['email'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password']         ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';
            if (empty($password)) {
                $erreur = 'Veuillez entrer un nouveau mot de passe.';
            } elseif (strlen($password) < 6) {
                $erreur = 'Le mot de passe doit avoir au moins 6 caracteres.';
            } elseif ($password !== $confirm) {
                $erreur = 'Les mots de passe ne correspondent pas.';
            } else {
                $this->userModel->mettreAJourMotDePasse($email, $password);
                $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
                header('Location: index.php?route=login&reset=1');
                exit;
            }
        }

        require __DIR__ . '/../Views/auth/reset.php';
    }

    // ============================================
    // ACTIVATION DE COMPTE
    // ============================================
    public function activerCompte(): void
    {
        $erreur = '';
        $token  = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

        if (empty($token)) {
            header('Location: index.php?route=login');
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitation) {
            $erreur = 'Ce lien d\'activation est invalide ou a expire. Contactez votre administrateur pour en recevoir un nouveau.';
            require __DIR__ . '/../Views/auth/activer.php';
            return;
        }

        $email = $invitation['email'];
        $user  = $this->userModel->findByEmail($email);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password']         ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';

            if (empty($password)) {
                $erreur = 'Veuillez choisir un mot de passe.';
            } elseif (strlen($password) < 6) {
                $erreur = 'Le mot de passe doit avoir au moins 6 caracteres.';
            } elseif ($password !== $confirm) {
                $erreur = 'Les mots de passe ne correspondent pas.';
            } else {
                $this->userModel->activerAvecMotDePasse($email, $password);

                if ($user) {
                    $this->qrCodeModel->genererSiAbsent($user['id'], 16);
                    $this->logAction('activation', 'users', $user['id']);
                }

                $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);

                unset($_SESSION['user']);

                header('Location: index.php?route=login&active=1');
                exit;
            }
        }

        require __DIR__ . '/../Views/auth/activer.php';
    }

    // ============================================
    // MODIFIER MON PROFIL (AJAX)
    // ============================================
    public function modifierProfil(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonProfil(false, 'Methode non autorisee');
            return;
        }

        $userId = $_SESSION['user']['id'] ?? '';
        if (empty($userId)) {
            $this->jsonProfil(false, 'Non connecte');
            return;
        }

        $nom      = trim($_POST['nom']      ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';

        if (empty($nom) || empty($email)) {
            $this->jsonProfil(false, 'Nom et email requis');
            return;
        }

        if (!empty($password) && strlen($password) < 6) {
            $this->jsonProfil(false, 'Le mot de passe doit avoir au moins 6 caracteres');
            return;
        }

        $this->userModel->mettreAJourProfil($userId, $nom, $email, $password ?: null);

        $_SESSION['user']['nom']   = $nom;
        $_SESSION['user']['email'] = $email;

        $this->logAction('update_profil', 'users', $userId);

        $this->jsonProfil(true, 'Profil mis a jour avec succes');
    }

    private function jsonProfil(bool $success, string $message): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    private function urlBase(): string
    {
        $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $hote      = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $chemin    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/pointagepro/public')), '/');
        return $protocole . '://' . $hote . $chemin;
    }

    private function envoyerEmailReset(string $email, string $lien): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'diopmouhamed101005@gmail.com';
            $mail->Password   = 'dmhxuyqmitzsecrx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('diopmouhamed101005@gmail.com', 'PointagePro');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Reinitialisation de votre mot de passe — PointagePro';
            $mail->Body    = '
            <div style="font-family:Arial,sans-serif;background:#0a0e1a;padding:40px;max-width:500px;margin:0 auto;border-radius:12px">
              <div style="text-align:center;margin-bottom:30px">
                <h1 style="color:#34d399;margin:0">PointagePro</h1>
                <p style="color:#64748b">Reinitialisation du mot de passe</p>
              </div>
              <div style="background:#0f1629;border:1px solid #1e2a4a;border-radius:12px;padding:24px;margin-bottom:24px">
                <p style="color:#94a3b8">Bonjour,</p>
                <p style="color:#94a3b8">Cliquez sur le bouton ci-dessous pour reinitialiser votre mot de passe.</p>
                <div style="text-align:center;margin:24px 0">
                  <a href="' . $lien . '" style="background:#059669;color:white;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:bold;font-size:15px;display:inline-block">
                    Reinitialiser mon mot de passe
                  </a>
                </div>
                <p style="color:#475569;font-size:13px;text-align:center">Ce lien expire dans <strong style="color:#fbbf24">1 heure</strong>.</p>
              </div>
              <p style="color:#475569;font-size:12px;text-align:center">
                Si vous n\'avez pas demande cette reinitialisation, ignorez cet email.<br>
                &copy; ' . date('Y') . ' PointagePro
              </p>
            </div>';
            $mail->AltBody = 'Lien de reinitialisation : ' . $lien;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer: ' . $mail->ErrorInfo);
            return false;
        }
    }

    private function logAction(string $action, string $entity = '', string $entityId = ''): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt   = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, entity, entity_id, ip)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $action, $entity, $entityId, $ip]);
        } catch (\Exception $e) {}
    }
}