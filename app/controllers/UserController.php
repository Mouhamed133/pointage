<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/QrCode.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController
{
    private PDO $db;
    private User $userModel;
    private QrCode $qrCodeModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->userModel = new User();
        $this->qrCodeModel = new QrCode();
    }

    // ============================================
    // LISTE DES ETUDIANTS (AJAX)
    // ============================================
    public function liste(): void
    {
        $search = $_GET['search'] ?? '';
        $dept   = $_GET['dept']   ?? '';

        $etudiants = $this->userModel->listeEtudiants($search, $dept);

        foreach ($etudiants as &$e) {
            $qr = $this->qrCodeModel->parUtilisateur($e['id']);
            $e['qr_token'] = $qr ? $qr['token'] : null;
        }

        $this->json(true, 'OK', $etudiants);
    }

    // ============================================
    // CREER UN ETUDIANT — invitation par email (AJAX)
    // L'admin ne definit plus de mot de passe : l'etudiant recoit un
    // email et choisit lui-meme son mot de passe via le lien d'activation.
    // ============================================
    public function creer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $nom   = trim($_POST['nom']        ?? '');
        $email = trim($_POST['email']      ?? '');
        $dept  = trim($_POST['department'] ?? '');

        if (empty($nom) || empty($email) || empty($dept)) {
            $this->json(false, 'Tous les champs sont requis');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(false, 'Format d\'email invalide');
            return;
        }

        if (!$this->domaineEmailValide($email)) {
            $this->json(false, 'Cette adresse email n\'existe pas : son domaine ne peut pas recevoir de courrier. Verifiez l\'orthographe.');
            return;
        }

        if ($this->userModel->emailExiste($email)) {
            $this->json(false, 'Cet email est deja utilise');
            return;
        }

        $id = $this->userModel->creerInviteParAdmin($nom, $email, $dept);

        // Genere le token d'activation (reutilise la table password_resets)
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        $token = bin2hex(random_bytes(32));
        $stmt  = $this->db->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $stmt->execute([$email, $token]);

        $lien   = $this->urlBase() . '/index.php?route=activer&token=' . $token;
        $envoye = $this->envoyerEmailInvitation($email, $nom, $lien);

        $this->logAction('create', 'users', $id);

        if ($envoye) {
            $this->json(true, 'Etudiant ajoute. Un email d\'invitation lui a ete envoye.', [
                'id'    => $id,
                'nom'   => $nom,
                'email' => $email,
                'dept'  => $dept,
            ]);
        } else {
            $this->json(true, 'Etudiant ajoute, mais l\'email n\'a pas pu etre envoye (verifiez la config SMTP).', [
                'id'    => $id,
                'nom'   => $nom,
                'email' => $email,
                'dept'  => $dept,
            ]);
        }
    }

    // ============================================
    // EMAIL D'INVITATION (PHPMailer)
    // ============================================
    private function envoyerEmailInvitation(string $email, string $nom, string $lien): bool
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
            $mail->Subject = 'Votre compte PointagePro vous attend';
            $mail->Body    = '
            <div style="font-family:Arial,sans-serif;background:#0a0e1a;padding:40px;max-width:500px;margin:0 auto;border-radius:12px">
              <div style="text-align:center;margin-bottom:30px">
                <h1 style="color:#34d399;margin:0">PointagePro</h1>
                <p style="color:#64748b">Activation de votre compte</p>
              </div>
              <div style="background:#0f1629;border:1px solid #1e2a4a;border-radius:12px;padding:24px;margin-bottom:24px">
                <p style="color:#94a3b8">Bonjour ' . htmlspecialchars($nom) . ',</p>
                <p style="color:#94a3b8">Un compte PointagePro a ete cree pour vous. Cliquez sur le bouton ci-dessous pour l\'activer et choisir votre mot de passe.</p>
                <div style="text-align:center;margin:24px 0">
                  <a href="' . $lien . '" style="background:#059669;color:white;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:bold;font-size:15px;display:inline-block">
                    Activer mon compte
                  </a>
                </div>
                <p style="color:#475569;font-size:13px;text-align:center">Ce lien expire dans <strong style="color:#fbbf24">1 heure</strong>.</p>
              </div>
              <p style="color:#475569;font-size:12px;text-align:center">
                Si vous ne vous attendiez pas a cet email, vous pouvez l\'ignorer.<br>
                &copy; ' . date('Y') . ' PointagePro
              </p>
            </div>';
            $mail->AltBody = 'Activez votre compte PointagePro : ' . $lien;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer: ' . $mail->ErrorInfo);
            return false;
        }
    }

    // ============================================
    // MODIFIER UN ETUDIANT (AJAX)
    // ============================================
    public function modifier(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
            $this->json(false, 'Acces refuse');
            return;
        }

        $id       = trim($_POST['id']         ?? '');
        $nom      = trim($_POST['nom']        ?? '');
        $email    = trim($_POST['email']      ?? '');
        $dept     = trim($_POST['department'] ?? '');
        $password = $_POST['password']        ?? '';

        if (empty($id) || empty($nom) || empty($email)) {
            $this->json(false, 'Nom et email requis');
            return;
        }

        if (!empty($password) && strlen($password) < 6) {
            $this->json(false, 'Le mot de passe doit avoir au moins 6 caracteres');
            return;
        }

        $this->userModel->modifierEtudiant($id, $nom, $email, $dept, $password ?: null);

        $this->logAction('update', 'users', $id);
        $this->json(true, 'Etudiant modifie avec succes');
    }

    // ============================================
    // RENVOYER L'INVITATION (AJAX)
    // Regenere un nouveau token et renvoie l'email d'activation.
    // Utile si le lien precedent a expire (1h) ou a ete perdu.
    // ============================================
    public function renvoyerInvitation(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
            $this->json(false, 'Acces refuse');
            return;
        }

        $id = trim($_POST['id'] ?? '');
        if (empty($id)) {
            $this->json(false, 'ID manquant');
            return;
        }

        $etudiant = $this->userModel->findById($id);

        if (!$etudiant) {
            $this->json(false, 'Etudiant introuvable');
            return;
        }

        if ($etudiant['is_active']) {
            $this->json(false, 'Ce compte est deja actif');
            return;
        }

        $email = $etudiant['email'];
        $nom   = $etudiant['nom'];

        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        $token = bin2hex(random_bytes(32));
        $stmt  = $this->db->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $stmt->execute([$email, $token]);

        $lien   = $this->urlBase() . '/index.php?route=activer&token=' . $token;
        $envoye = $this->envoyerEmailInvitation($email, $nom, $lien);

        $this->logAction('resend_invitation', 'users', $id);

        if ($envoye) {
            $this->json(true, 'Invitation renvoyee a ' . $nom);
        } else {
            $this->json(false, 'Erreur envoi email. Verifiez la config SMTP.');
        }
    }

    // ============================================
    // DESACTIVER UN ETUDIANT (AJAX)
    // ============================================
    public function supprimer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
            $this->json(false, 'Acces refuse');
            return;
        }

        $id = trim($_POST['id'] ?? '');
        if (empty($id)) {
            $this->json(false, 'ID manquant');
            return;
        }

        $this->userModel->desactiver($id);

        $this->logAction('delete', 'users', $id);
        $this->json(true, 'Etudiant desactive');
    }

    // ============================================
    // LISTE COMPTES EN ATTENTE (AJAX)
    // ============================================
    public function listeEnAttente(): void
    {
        if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
            $this->json(false, 'Acces refuse');
            return;
        }

        $comptes = $this->userModel->listeEnAttente();

        $this->json(true, 'OK', $comptes);
    }

    // ============================================
    // APPROUVER UN COMPTE (AJAX)
    // ============================================
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

        $id = trim($_POST['id'] ?? '');
        if (empty($id)) {
            $this->json(false, 'ID manquant');
            return;
        }

        $this->userModel->activer($id);

        // Generer QR code si pas encore fait
        $this->qrCodeModel->genererSiAbsent($id, 16);

        $this->logAction('approve', 'users', $id);
        $this->json(true, 'Compte approuve et QR Code genere');
    }

    // ============================================
    // REFUSER UN COMPTE (AJAX)
    // ============================================
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

        $id = trim($_POST['id'] ?? '');
        if (empty($id)) {
            $this->json(false, 'ID manquant');
            return;
        }

        $this->userModel->supprimerEnAttente($id);

        $this->logAction('refuse', 'users', $id);
        $this->json(true, 'Compte refuse et supprime');
    }

    // ============================================
    // TELECHARGER LE MODELE EXCEL D'IMPORT
    // ============================================
    public function telechargerModele(): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Etudiants');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'],
            ],
        ];

        $sheet->setCellValue('A1', 'nom');
        $sheet->setCellValue('B1', 'email');
        $sheet->setCellValue('C1', 'department');
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $exemples = [
            ['Moussa Diallo', 'moussa.diallo@exemple.sn', 'Informatique'],
            ['Aminata Fall', 'aminata.fall@exemple.sn', 'Gestion'],
        ];
        $ligne = 2;
        foreach ($exemples as $ex) {
            $sheet->setCellValue('A' . $ligne, $ex[0]);
            $sheet->setCellValue('B' . $ligne, $ex[1]);
            $sheet->setCellValue('C' . $ligne, $ex[2]);
            $ligne++;
        }

        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Rappel des departements valides, a cote pour reference
        $sheet->setCellValue('E1', 'Departements valides :');
        $sheet->getStyle('E1')->applyFromArray(['font' => ['bold' => true]]);
        $departements = ['Informatique', 'Gestion', 'Commerce', 'Comptabilite', 'Communication'];
        $l = 2;
        foreach ($departements as $d) {
            $sheet->setCellValue('E' . $l, $d);
            $l++;
        }
        $sheet->getColumnDimension('E')->setAutoSize(true);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="modele_import_etudiants.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ============================================
    // IMPORTER PLUSIEURS ETUDIANTS DEPUIS UN FICHIER EXCEL (AJAX)
    // Chaque ligne valide cree un compte invite (meme flux que creer()).
    // Les lignes invalides sont rapportees sans bloquer les autres.
    // ============================================
    public function importerExcel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
            $this->json(false, 'Acces refuse');
            return;
        }

        if (empty($_FILES['fichier']['name'])) {
            $this->json(false, 'Aucun fichier selectionne');
            return;
        }

        $file = $_FILES['fichier'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->json(false, 'Format non supporte. Utilisez un fichier Excel (.xlsx)');
            return;
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $lignes = $sheet->toArray(null, true, true, true); // cles : ligne reelle => [A=>.., B=>.., C=>..]
        } catch (\Exception $e) {
            $this->json(false, 'Impossible de lire le fichier Excel. Verifiez qu\'il n\'est pas corrompu.');
            return;
        }

        $departementsValides = ['Informatique', 'Gestion', 'Commerce', 'Comptabilite', 'Communication'];
        $reussis    = [];
        $erreurs    = [];
        $emailsVus  = [];

        foreach ($lignes as $numeroLigne => $ligne) {
            if ($numeroLigne == 1) {
                continue; // ligne d'en-tete
            }

            $nom   = trim((string) ($ligne['A'] ?? ''));
            $email = trim((string) ($ligne['B'] ?? ''));
            $dept  = trim((string) ($ligne['C'] ?? ''));

            if ($nom === '' && $email === '' && $dept === '') {
                continue; // ligne vide, on l'ignore silencieusement
            }

            if ($nom === '' || $email === '' || $dept === '') {
                $erreurs[] = "Ligne $numeroLigne : nom, email ou departement manquant";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = "Ligne $numeroLigne : email invalide ($email)";
                continue;
            }

            if (!$this->domaineEmailValide($email)) {
                $erreurs[] = "Ligne $numeroLigne : domaine email inexistant ($email)";
                continue;
            }

            if (in_array(strtolower($email), $emailsVus)) {
                $erreurs[] = "Ligne $numeroLigne : email en double dans le fichier ($email)";
                continue;
            }

            // Comparaison insensible a la casse/espaces pour eviter les faux rejets
            $deptValide = null;
            foreach ($departementsValides as $d) {
                if (strcasecmp($dept, $d) === 0) {
                    $deptValide = $d;
                    break;
                }
            }
            if ($deptValide === null) {
                $erreurs[] = "Ligne $numeroLigne : departement invalide \"$dept\" (valeurs autorisees : " . implode(', ', $departementsValides) . ')';
                continue;
            }

            if ($this->userModel->emailExiste($email)) {
                $erreurs[] = "Ligne $numeroLigne : email deja utilise ($email)";
                continue;
            }

            $emailsVus[] = strtolower($email);

            $id = $this->userModel->creerInviteParAdmin($nom, $email, $deptValide);

            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            $token = bin2hex(random_bytes(32));
            $stmt  = $this->db->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $stmt->execute([$email, $token]);

            $lienActivation = $this->urlBase() . '/index.php?route=activer&token=' . $token;
            $envoye = $this->envoyerEmailInvitation($email, $nom, $lienActivation);

            $reussis[] = $nom . ' (' . $email . ')' . ($envoye ? '' : ' — email non envoye, verifiez SMTP');

            $this->logAction('create', 'users', $id);
        }

        $message = count($reussis) . ' etudiant(s) importe(s)';
        if (count($erreurs) > 0) {
            $message .= ', ' . count($erreurs) . ' erreur(s)';
        }

        $this->json(true, $message, [
            'reussis' => $reussis,
            'erreurs' => $erreurs,
        ]);
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Verifie que le domaine de l'email peut recevoir du courrier
     * (enregistrement DNS MX, ou A en repli pour les rares domaines
     * qui acheminent le mail sans MX explicite). Ne garantit pas que
     * la boite mail precise existe, mais elimine les domaines
     * inventes, mal orthographies ou inexistants.
     */
    private function domaineEmailValide(string $email): bool
    {
        $arobase = strrpos($email, '@');
        if ($arobase === false) {
            return false;
        }

        $domaine = substr($email, $arobase + 1);
        if (empty($domaine)) {
            return false;
        }

        return checkdnsrr($domaine, 'MX') || checkdnsrr($domaine, 'A');
    }

    private function json(bool $success, string $message, array $data = []): void
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

    
    
    private function urlBase(): string
    {
        $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $hote      = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $chemin    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/pointagepro/public')), '/');

        return $protocole . '://' . $hote . $chemin;
    }
}