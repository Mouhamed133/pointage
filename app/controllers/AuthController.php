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
        $this->userModel      = new User();
        $this->attendanceModel = new Attendance();
        $this->qrCodeModel    = new QrCode();
    }

    // ============================================================
    // LOGIN
    // ============================================================
    public function login(): void
    {
        $erreur     = '';
        $scanMode   = isset($_GET['scan']) && $_GET['scan'] === '1';
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
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id'         => $user['id'],
                        'nom'        => $user['nom'],
                        'email'      => $user['email'],
                        'role'       => $user['role'],
                        'department' => $user['department'],
                        'cohorte_id' => $user['cohorte_id'],
                        'telephone'  => $user['telephone'] ?? '',
                        'photo'      => $user['photo']     ?? '',
                    ];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $_SESSION['last_activity'] = time();

                    // Log avec l'ID explicite (évite user_id NULL dans audit_logs)
                    $this->logAction('login', 'users', '', $user['id']);

                    if (!empty($_SESSION['scan_pending_token'])) {
                        header('Location: index.php?route=scan/pointer');
                        exit;
                    }

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

    public function register(): void
    {
        header('Location: index.php?route=login&inscription_desactivee=1');
        exit;
    }

    public function logout(): void
    {
        $userId = $_SESSION['user']['id'] ?? '';
        $this->logAction('logout', 'users', '', $userId);

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_unset();
        session_destroy();
        header('Location: index.php?route=login');
        exit;
    }

    // ============================================================
    // DASHBOARD ADMIN
    // ============================================================
    public function dashboard(): void
    {
        $today          = date('Y-m-d');
        $totalEtudiants = $this->userModel->compterEtudiantsActifs();
        $presents       = $this->attendanceModel->compterParTypeEtDate($today, ['present', 'retard']);
        $retards        = $this->attendanceModel->compterParTypeEtDate($today, ['retard']);
        $absents        = max(0, $totalEtudiants - $presents);
        $derniersPointages = $this->attendanceModel->dernierPointagesDuJour($today, 10);
        require __DIR__ . '/../Views/dashboard/index.php';
    }

    public function stats(): void
    {
        $today          = date('Y-m-d');
        $totalEtudiants = $this->userModel->compterEtudiantsActifs();
        $presents       = $this->attendanceModel->compterParTypeEtDate($today, ['present', 'retard']);
        $retards        = $this->attendanceModel->compterParTypeEtDate($today, ['retard']);
        $absents        = max(0, $totalEtudiants - $presents);
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

    // ============================================================
    // AUDIT LOG — affiche TOUS les utilisateurs (admin + étudiants)
    // ============================================================
    public function auditListe(): void
    {
        $page   = intval($_GET['page']   ?? 1);
        $search = trim($_GET['search']   ?? '');
        $action = trim($_GET['action']   ?? '');
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        // LEFT JOIN users pour récupérer nom/email/role de QUI a fait l'action
        // COALESCE gère les user_id NULL (connexions anonymes ou sessions expirées)
        $sql = "
SELECT
    a.*,

    COALESCE(u.nom,'Inconnu') AS nom,
    COALESCE(u.email,'') AS email,
    COALESCE(u.role,'') AS role,

    COALESCE(e.nom,'') AS entity_nom

FROM audit_logs a

LEFT JOIN users u 
    ON u.id = a.user_id

LEFT JOIN users e
    ON e.id = a.entity_id

WHERE 1=1
";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (
                COALESCE(u.nom,  '') LIKE ? OR
                COALESCE(u.email,'') LIKE ? OR
                a.action LIKE ? OR
                a.entity LIKE ?
            )";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($action)) {
            $sql .= " AND a.action = ?";
            $params[] = $action;
        }

        // COUNT
        $sqlCount = "
            SELECT COUNT(*) as total
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            WHERE 1=1
        ";
        if (!empty($search)) {
            $sqlCount .= " AND (COALESCE(u.nom,'') LIKE ? OR COALESCE(u.email,'') LIKE ? OR a.action LIKE ? OR a.entity LIKE ?)";
        }
        if (!empty($action)) {
            $sqlCount .= " AND a.action = ?";
        }

        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        $sql .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtActions = $this->db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
        $actions     = $stmtActions->fetchAll(PDO::FETCH_COLUMN);

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

    // ============================================================
    // MOT DE PASSE OUBLIE
    // ============================================================
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
                    $lien   = $this->urlBase() . '/index.php?route=reset&token=' . $token;
                    $envoye = $this->envoyerEmailReset($email, $lien);
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

    // ============================================================
    // RESET MOT DE PASSE
    // ============================================================
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

    // ============================================================
    // ACTIVATION DE COMPTE
    // ============================================================
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
                    $this->logAction('activation', 'users', '', $user['id']);
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

    // ============================================================
    // MODIFIER MON PROFIL — photo + téléphone + mot de passe
    // ============================================================
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

        $nom       = trim($_POST['nom']       ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $password  = $_POST['password']       ?? '';
        $confirm   = $_POST['password_confirm'] ?? '';

        if (empty($nom) || empty($email)) {
            $this->jsonProfil(false, 'Nom et email requis');
            return;
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                $this->jsonProfil(false, 'Le mot de passe doit avoir au moins 6 caracteres');
                return;
            }
            if ($password !== $confirm) {
                $this->jsonProfil(false, 'Les mots de passe ne correspondent pas');
                return;
            }
        }

        if (!empty($telephone) && !preg_match('/^[\+\d\s\-\(\)]{7,20}$/', $telephone)) {
            $this->jsonProfil(false, 'Numero de telephone invalide');
            return;
        }

        // Gestion photo
        $photoNom = $_SESSION['user']['photo'] ?? '';
        if (!empty($_FILES['photo']['name'])) {
            $file      = $_FILES['photo'];
            $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $typesOk   = ['jpg', 'jpeg', 'png', 'webp'];
            $tailleMax = 2 * 1024 * 1024;

            if (!in_array($ext, $typesOk)) {
                $this->jsonProfil(false, 'Format photo non autorise (jpg, jpeg, png, webp)');
                return;
            }
            if ($file['size'] > $tailleMax) {
                $this->jsonProfil(false, 'Photo trop lourde (max 2 MB)');
                return;
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                $this->jsonProfil(false, 'Fichier invalide — image requise');
                return;
            }

            $dossier = __DIR__ . '/../../public/uploads/photos/';
            if (!is_dir($dossier)) mkdir($dossier, 0755, true);
            if (!empty($photoNom) && file_exists($dossier . $photoNom)) unlink($dossier . $photoNom);

            $photoNom = 'user_' . $userId . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $dossier . $photoNom);
        }

        $this->userModel->mettreAJourProfil($userId, $nom, $email, $password ?: null, $telephone, $photoNom);

        $_SESSION['user']['nom']       = $nom;
        $_SESSION['user']['email']     = $email;
        $_SESSION['user']['telephone'] = $telephone;
        $_SESSION['user']['photo']     = $photoNom;

        $this->logAction('update_profil', 'users', '', $userId);

        $this->jsonProfil(true, 'Profil mis a jour avec succes', [
            'photo' => $photoNom ? 'uploads/photos/' . $photoNom : '',
            'nom'   => $nom,
        ]);
    }

    // ============================================================
    // SUPPRIMER LA PHOTO DE PROFIL
    // ============================================================
    public function supprimerPhoto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonProfil(false, 'Methode non autorisee');
            return;
        }

        $userId   = $_SESSION['user']['id']   ?? '';
        $photoNom = $_SESSION['user']['photo'] ?? '';

        if (!empty($photoNom)) {
            $dossier = __DIR__ . '/../../public/uploads/photos/';
            if (file_exists($dossier . $photoNom)) unlink($dossier . $photoNom);
        }

        $stmt = $this->db->prepare("UPDATE users SET photo = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['user']['photo'] = '';

        $this->logAction('supprimer_photo', 'users', '', $userId);
        $this->jsonProfil(true, 'Photo supprimee');
    }

    // ============================================================
    // HELPERS PRIVÉS
    // ============================================================
    private function jsonProfil(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
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
            $mail->Password   = 'wdmjmzyigtqbodiz';
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

    // ============================================================
    // LOG ACTION — user_id passé explicitement pour éviter les NULL
    // ============================================================
    private function logAction(
        string $action,
        string $entity     = '',
        string $entityId   = '',
        string $forceUserId = ''
    ): void {
        try {
            // Priorité : ID passé en paramètre, sinon session
            $userId = !empty($forceUserId)
                ? $forceUserId
                : ($_SESSION['user']['id'] ?? null);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, entity, entity_id, ip)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $action, $entity, $entityId ?: null, $ip]);
        } catch (\Exception $e) {
        }
    }
}
