<?php
/**
 * je met cette commande pour le forcer a envoyer les emails a l'instant (schtasks /run /tn "PointagePro_Alertes_Absences")
 * ============================================================
 * CRON JOB — Alertes Absences Quotidiennes
 * ============================================================
 * Exécution : tous les jours à 16h00
 * Crontab   : 0 16 * * 1-6 /usr/bin/php /var/www/html/cron/send_absence_alerts.php
 *
 * Ce script :
 * 1. Récupère tous les étudiants actifs
 * 2. Identifie ceux qui n'ont aucun pointage aujourd'hui
 * 3. Exclut ceux qui ont une absence approuvée pour aujourd'hui
 * 4. Envoie un email personnalisé à chaque absent
 * 5. Logge les résultats dans la base de données
 * ============================================================
 */

// Sécurité : ce script ne doit être appelé que par le serveur
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_secret'])) {
    http_response_code(403);
    exit('Acces interdit');
}
if (isset($_GET['cron_secret']) && $_GET['cron_secret'] !== getenv('CRON_SECRET')) {
    http_response_code(403);
    exit('Cle invalide');
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================
// CONFIG EMAIL — à adapter selon votre hébergeur
// ============================================================
define('MAIL_HOST',     getenv('MAIL_HOST')     ?: 'smtp.gmail.com');
define('MAIL_PORT',     getenv('MAIL_PORT')     ?: 587);
define('MAIL_USER',     getenv('MAIL_USER')     ?: 'diopmouhamed101005@gmail.com');
define('MAIL_PASS',     getenv('MAIL_PASS')     ?: 'wdmjmzyigtqbodiz'); // le vrai mot de passe app 16 car
define('MAIL_FROM',     getenv('MAIL_FROM')     ?: 'diopmouhamed101005@gmail.com');
define('MAIL_FROM_NAME',getenv('MAIL_FROM_NAME')?: 'PointagePro');
define('APP_URL',       getenv('APP_URL')        ?: 'https://isocheimal-unheroically-hong.ngrok-free.dev/pointagepro/public/index.php?route=login');
define('APP_NOM',       getenv('APP_NOM')        ?: 'PointagePro');

// ============================================================
// CONNEXION BASE DE DONNÉES
// ============================================================
$database = new Database();
$db = $database->getConnection();

$today     = date('Y-m-d');
$dayOfWeek = date('N'); // 1=Lundi ... 7=Dimanche

// Ne pas envoyer le dimanche (7)
if ($dayOfWeek == 7) {
    log_cron($db, 'INFO', 'Dimanche — aucun envoi');
    exit(0);
}

// ============================================================
// ÉTAPE 1 : Récupérer tous les étudiants actifs
// ============================================================
$stmtEtu = $db->prepare("
    SELECT id, nom, email, department
    FROM users
    WHERE role = 'etudiant'
      AND is_active = 1
    ORDER BY nom ASC
");
$stmtEtu->execute();
$tousEtudiants = $stmtEtu->fetchAll(PDO::FETCH_ASSOC);

if (empty($tousEtudiants)) {
    log_cron($db, 'INFO', 'Aucun etudiant actif trouvé');
    exit(0);
}

// ============================================================
// ÉTAPE 2 : Récupérer les étudiants qui ont pointé aujourd'hui
// ============================================================
$stmtPointed = $db->prepare("
    SELECT DISTINCT user_id
    FROM attendances
    WHERE DATE(date) = :today
      AND type IN ('present', 'retard')
");
$stmtPointed->execute([':today' => $today]);
$idsPresents = $stmtPointed->fetchAll(PDO::FETCH_COLUMN);

// ============================================================
// ÉTAPE 3 : Récupérer les étudiants avec absence justifiée/approuvée aujourd'hui
// ============================================================
$stmtConges = $db->prepare("
    SELECT DISTINCT user_id
    FROM leaves
    WHERE status = 'approuve'
      AND :today BETWEEN start_date AND end_date
");
$stmtConges->execute([':today' => $today]);
$idsConges = $stmtConges->fetchAll(PDO::FETCH_COLUMN);

// ============================================================
// ÉTAPE 4 : Identifier les absents sans justification
// ============================================================
$idsExclus = array_unique(array_merge($idsPresents, $idsConges));

$absents = array_filter($tousEtudiants, function($etu) use ($idsExclus) {
    return !in_array($etu['id'], $idsExclus);
});

if (empty($absents)) {
    log_cron($db, 'INFO', 'Aucun absent non justifié aujourd\'hui (' . $today . ')');
    exit(0);
}

log_cron($db, 'INFO', count($absents) . ' absent(s) détecté(s) pour le ' . $today);

// ============================================================
// ÉTAPE 5 : Envoyer un email à chaque absent
// ============================================================
$nbEnvoyes  = 0;
$nbEchecs   = 0;
$erreurs    = [];

$jourFr = [
    1 => 'lundi', 2 => 'mardi', 3 => 'mercredi',
    4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi'
];
$nomJour = $jourFr[$dayOfWeek] ?? date('l');
$dateFormatee = date('d/m/Y');

foreach ($absents as $etu) {
    $sujet = '[' . APP_NOM . '] Absence du ' . $dateFormatee . ' — Justification requise';
    $corps = genererEmailAbsence($etu, $nomJour, $dateFormatee);

    $ok = envoyerEmail($etu['email'], $etu['nom'], $sujet, $corps);

    if ($ok) {
        $nbEnvoyes++;
        // Enregistrer le pointage comme "absence" si pas encore enregistré
        enregistrerAbsence($db, $etu['id'], $today);
        log_cron($db, 'EMAIL_OK', 'Email envoyé à ' . $etu['email'] . ' (' . $etu['nom'] . ')');
    } else {
        $nbEchecs++;
        $erreurs[] = $etu['email'];
        log_cron($db, 'EMAIL_FAIL', 'Échec envoi à ' . $etu['email']);
    }

    // Petite pause pour ne pas surcharger le serveur SMTP
    usleep(300000); // 300ms
}

// ============================================================
// RÉSUMÉ FINAL
// ============================================================
$resume = "Résumé du {$today} : {$nbEnvoyes} email(s) envoyé(s), {$nbEchecs} échec(s)";
if (!empty($erreurs)) {
    $resume .= ' — Échecs : ' . implode(', ', $erreurs);
}
log_cron($db, 'DONE', $resume);
echo $resume . PHP_EOL;
exit(0);


// ============================================================
// FONCTIONS
// ============================================================

/**
 * Génère le HTML de l'email d'absence
 */
function genererEmailAbsence(array $etu, string $nomJour, string $dateFormatee): string
{
    $nomEtu  = htmlspecialchars($etu['nom']);
    $deptEtu = htmlspecialchars($etu['department'] ?? '');
    $appUrl  = APP_URL;
    $appNom  = APP_NOM;

    return '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Absence non justifiée</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:30px 0">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">

          <!-- HEADER -->
          <tr>
            <td style="background:#0a0e1a;padding:28px 36px">
              <table width="100%">
                <tr>
                  <td>
                    <h1 style="color:#34d399;margin:0;font-size:22px;font-weight:700">' . $appNom . '</h1>
                    <p style="color:#64748b;margin:4px 0 0;font-size:12px">Système de Gestion des Présences</p>
                  </td>
                  <td align="right">
                    <div style="background:#2d1414;border:1px solid #7f1d1d;border-radius:8px;padding:8px 14px;display:inline-block">
                      <p style="color:#f87171;margin:0;font-size:12px;font-weight:700">⚠ ABSENCE DÉTECTÉE</p>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- CORPS -->
          <tr>
            <td style="padding:36px">

              <p style="color:#1e293b;font-size:16px;margin:0 0 8px">Bonjour <strong>' . $nomEtu . '</strong>,</p>

              <p style="color:#475569;font-size:14px;line-height:1.7;margin:0 0 24px">
                Nous avons constaté que vous étiez <strong style="color:#dc2626">absent(e)</strong> 
                ce <strong>' . $nomJour . ' ' . $dateFormatee . '</strong> et qu\'aucun pointage 
                n\'a été enregistré en votre nom.
              </p>

              <!-- BLOC ABSENCE -->
              <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:20px;margin-bottom:24px">
                <table width="100%">
                  <tr>
                    <td style="font-size:32px;width:50px">📋</td>
                    <td>
                      <p style="color:#dc2626;font-weight:700;font-size:15px;margin:0 0 4px">Absence non justifiée</p>
                      <p style="color:#64748b;font-size:13px;margin:0">
                        Date : <strong>' . $dateFormatee . '</strong><br>
                        Département : <strong>' . $deptEtu . '</strong>
                      </p>
                    </td>
                  </tr>
                </table>
              </div>

              <!-- DÉMARCHE REQUISE -->
              <p style="color:#1e293b;font-size:14px;font-weight:700;margin:0 0 12px">
                📌 Action requise avant votre prochain retour :
              </p>

              <table width="100%" style="margin-bottom:24px">
                <tr>
                  <td style="vertical-align:top;padding:10px 14px;background:#f0fdf4;border-radius:10px;margin-bottom:8px">
                    <p style="margin:0;color:#166534;font-size:13px;line-height:1.6">
                      <strong>1.</strong> Connectez-vous à <strong>' . $appNom . '</strong><br>
                      <strong>2.</strong> Accédez à la section <em>Gestion Absences</em><br>
                      <strong>3.</strong> Soumettez une demande de justification avec le motif de votre absence<br>
                      <strong>4.</strong> Joignez un justificatif si disponible (certificat médical, convocation…)
                    </p>
                  </td>
                </tr>
              </table>

              <!-- BOUTON CTA -->
              <div style="text-align:center;margin:28px 0">
                <a href="' . $appUrl . '" 
                   style="background:#059669;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:10px;font-size:15px;font-weight:700;display:inline-block">
                  Justifier mon absence →
                </a>
              </div>

              <!-- AVERTISSEMENT -->
              <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:16px;margin-bottom:24px">
                <p style="color:#92400e;font-size:12px;margin:0;line-height:1.6">
                  ⚠️ <strong>Important :</strong> Toute absence non justifiée dans les 
                  <strong>48 heures</strong> sera considérée comme absence injustifiée et 
                  pourra avoir des conséquences sur votre dossier académique.
                </p>
              </div>

              <p style="color:#475569;font-size:13px;line-height:1.6;margin:0">
                Si vous pensez que cette notification est une erreur (pointage manqué, problème technique), 
                veuillez contacter l\'administration dès que possible.
              </p>

            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td style="background:#f8fafc;padding:20px 36px;border-top:1px solid #e2e8f0">
              <p style="color:#94a3b8;font-size:11px;margin:0;text-align:center;line-height:1.6">
                Cet email a été envoyé automatiquement par <strong>' . $appNom . '</strong> — 
                Ne pas répondre à cet email.<br>
                © ' . date('Y') . ' ' . $appNom . ' — Système de Gestion des Présences
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

/**
 * Envoie l'email via PHPMailer
 */
function envoyerEmail(string $toEmail, string $toNom, string $sujet, string $corps): bool
{
    $mail = new PHPMailer(true);
    try {
        // Config SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';


        // Expéditeur
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

        // Destinataire
        $mail->addAddress($toEmail, $toNom);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $corps;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $corps));

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[PointagePro Cron] Erreur PHPMailer: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Enregistre une ligne "absence" dans la table attendances si elle n'existe pas déjà
 */
function enregistrerAbsence(PDO $db, string $userId, string $date): void
{
    // Vérifier si une ligne existe déjà pour cet étudiant aujourd'hui
    $stmt = $db->prepare("
        SELECT id FROM attendances
        WHERE user_id = :user_id AND DATE(date) = :date
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $userId, ':date' => $date]);

    if (!$stmt->fetch()) {
        // Insérer une ligne absence
        $ins = $db->prepare("
            INSERT INTO attendances (user_id, date, type, check_in, check_out)
            VALUES (:user_id, :date, 'absence', NULL, NULL)
        ");
        $ins->execute([':user_id' => $userId, ':date' => $date]);
    }
}

/**
 * Enregistre une entrée dans la table audit_log
 */
function log_cron(PDO $db, string $action, string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] [' . $action . '] ' . $message . PHP_EOL;

    try {
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity, ip, created_at)
            VALUES (NULL, :action, :entity, 'cron', NOW())
        ");
        $stmt->execute([
            ':action' => 'cron_absence_alert',
            ':entity' => substr($message, 0, 255),
        ]);
    } catch (\Exception $e) {
        // Silencieux si la table n'existe pas
    }
}