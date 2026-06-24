<?php

require_once __DIR__ . '/../config/Database.php';

session_start();

$route = $_GET['route'] ?? 'login';
$route = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $route);

// 'scan' est public : l'etudiant non connecte peut arriver ici depuis le QR mural
$pagesPubliques = ['login', 'logout', 'register', 'forgot', 'reset', 'activer', 'scan'];

if (!isset($_SESSION['user']) && !in_array($route, $pagesPubliques)) {
    header('Location: index.php?route=login');
    exit;
}

if (isset($_SESSION['user']) && $route === 'login') {
    if ($_SESSION['user']['role'] === 'etudiant') {
        header('Location: index.php?route=etudiant/dashboard');
    } else {
        header('Location: index.php?route=dashboard');
    }
    exit;
}

switch ($route) {

    // ============================================
    // AUTH
    // ============================================
    case 'login':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->login();
        break;

    case 'reset':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->reset();
        break;

    case 'forgot':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->forgot();
        break;

    case 'register':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->register();
        break;

    case 'activer':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->activerCompte();
        break;

    case 'profil/modifier':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->modifierProfil();
        break;

    case 'logout':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    // ============================================
    // QR CODE MURAL — NOUVEAU FLUX
    //
    // route=scan         (public) : recoit le scan depuis le QR mural,
    //                               memorise le token, redirige vers login
    //                               ou pointe directement si deja connecte.
    //
    // route=scan/pointer (prive)  : appele apres login reussi pour effectuer
    //                               le pointage et rediriger au dashboard.
    // ============================================
    case 'scan':
        require_once __DIR__ . '/../app/Controllers/SchoolQrController.php';
        (new SchoolQrController())->scanRedirect();
        break;

    case 'scan/pointer':
        require_once __DIR__ . '/../app/Controllers/SchoolQrController.php';
        (new SchoolQrController())->pointerApresScan();
        break;

    // ============================================
    // ETUDIANT
    // ============================================
    case 'etudiant/dashboard':
        require_once __DIR__ . '/../app/Controllers/EtudiantController.php';
        (new EtudiantController())->dashboard();
        break;

    case 'justificatif/generer':
        require_once __DIR__ . '/../app/Controllers/JustificatifController.php';
        (new JustificatifController())->generer();
        break;

    case 'etudiant/conge':
        require_once __DIR__ . '/../app/Controllers/EtudiantController.php';
        (new EtudiantController())->demanderConge();
        break;

    case 'etudiant/qr/regenerer':
        require_once __DIR__ . '/../app/Controllers/EtudiantController.php';
        (new EtudiantController())->regenererQR();
        break;

    // ============================================
    // SCHOOL QR (admin)
    // ============================================
    case 'school/config':
        require_once __DIR__ . '/../app/Controllers/SchoolQrController.php';
        (new SchoolQrController())->getConfig();
        break;

    case 'school/config/update':
        require_once __DIR__ . '/../app/Controllers/SchoolQrController.php';
        (new SchoolQrController())->updateConfig();
        break;

    case 'school/qr':
        require_once __DIR__ . '/../app/Controllers/SchoolQrController.php';
        (new SchoolQrController())->getOrCreate();
        break;

    case 'school/qr/regenerer':
        require_once __DIR__ . '/../app/Controllers/SchoolQrController.php';
        (new SchoolQrController())->regenerer();
        break;

    case 'school/pointer':
        require_once __DIR__ . '/../app/Controllers/SchoolQrController.php';
        (new SchoolQrController())->pointer();
        break;

    // ============================================
    // RAPPORTS
    // ============================================
    case 'rapport/mensuel/pdf':
        require_once __DIR__ . '/../app/Controllers/ReportController.php';
        (new ReportController())->rapportMensuelPdf();
        break;

    case 'rapport/presences/excel':
        require_once __DIR__ . '/../app/Controllers/ReportController.php';
        (new ReportController())->fichePresenceExcel();
        break;

    case 'rapport/conges/pdf':
        require_once __DIR__ . '/../app/Controllers/ReportController.php';
        (new ReportController())->rapportCongesPdf();
        break;

    // ============================================
    // DASHBOARD & STATS
    // ============================================
    case 'audit/liste':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->auditListe();
        break;

    case 'dashboard/stats':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->stats();
        break;

    case 'dashboard':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->dashboard();
        break;

    // ============================================
    // PRESENCES
    // ============================================
    case 'presences/liste':
        require_once __DIR__ . '/../app/Controllers/AttendanceController.php';
        (new AttendanceController())->liste();
        break;

    case 'presences':
        require_once __DIR__ . '/../app/Controllers/AttendanceController.php';
        (new AttendanceController())->index();
        break;

    case 'presences/checkin':
        require_once __DIR__ . '/../app/Controllers/AttendanceController.php';
        (new AttendanceController())->checkin();
        break;

    case 'presences/pointer':
        require_once __DIR__ . '/../app/Controllers/AttendanceController.php';
        (new AttendanceController())->pointerAuto();
        break;

    case 'presences/checkout':
        require_once __DIR__ . '/../app/Controllers/AttendanceController.php';
        (new AttendanceController())->checkout();
        break;

    case 'qrcode/generer':
        require_once __DIR__ . '/../app/Controllers/AttendanceController.php';
        (new AttendanceController())->genererQR();
        break;

    // ============================================
    // ETUDIANTS (CRUD)
    // ============================================
    case 'etudiants/liste':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->liste();
        break;

    case 'etudiants/modifier':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->modifier();
        break;

    case 'etudiants/creer':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->creer();
        break;

    case 'etudiants/importer':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->importerExcel();
        break;

    case 'etudiants/modele':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->telechargerModele();
        break;

    case 'etudiants/supprimer':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->supprimer();
        break;

    // ============================================
    // VALIDATION (invitations)
    // ============================================
    case 'validation/liste':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->listeEnAttente();
        break;

    case 'validation/renvoyer':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->renvoyerInvitation();
        break;

    case 'validation/approuver':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->approuver();
        break;

    case 'validation/refuser':
        require_once __DIR__ . '/../app/Controllers/UserController.php';
        (new UserController())->refuser();
        break;

    // ============================================
    // CONGES
    // ============================================
    case 'conges/liste':
        require_once __DIR__ . '/../app/Controllers/LeaveController.php';
        (new LeaveController())->liste();
        break;

    case 'conges/soumettre':
        require_once __DIR__ . '/../app/Controllers/LeaveController.php';
        (new LeaveController())->soumettre();
        break;

    case 'conges/approuver':
        require_once __DIR__ . '/../app/Controllers/LeaveController.php';
        (new LeaveController())->approuver();
        break;

    case 'conges/refuser':
        require_once __DIR__ . '/../app/Controllers/LeaveController.php';
        (new LeaveController())->refuser();
        break;

    case 'conges/supprimer':
        require_once __DIR__ . '/../app/Controllers/LeaveController.php';
        (new LeaveController())->supprimer();
        break;

    // ============================================
    // 404
    // ============================================
    default:
        http_response_code(404);
        echo '<!DOCTYPE html><html><body style="font-family:monospace;background:#0a0e1a;color:#f87171;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;">
            <div style="text-align:center">
                <p style="font-size:64px;margin:0;color:#1e2a4a">404</p>
                <p>Page introuvable : <strong>' . htmlspecialchars($route) . '</strong></p>
                <a href="index.php?route=dashboard" style="color:#34d399;text-decoration:none">Retour au dashboard</a>
            </div></body></html>';
        break;
}