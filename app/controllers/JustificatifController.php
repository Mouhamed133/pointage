<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Leave.php';

class JustificatifController
{
    private PDO $db;
    private Attendance $attendanceModel;
    private Leave $leaveModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->attendanceModel = new Attendance();
        $this->leaveModel = new Leave();
    }

    public function generer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=etudiant/dashboard');
            exit;
        }

        $userId      = $_SESSION['user']['id'];
        $nom         = $_SESSION['user']['nom'];
        $dept        = $_SESSION['user']['department'] ?? '';
        $dateAbsence = trim($_POST['date_absence'] ?? '');
        $motif       = trim($_POST['motif']        ?? '');
        $details     = trim($_POST['details']      ?? '');

        if (empty($dateAbsence) || empty($motif)) {
            header('Location: index.php?route=etudiant/dashboard&erreur=champs_requis');
            exit;
        }

        $pointage = $this->attendanceModel->duJour($userId, $dateAbsence);

        $dateFormatee = date('d/m/Y', strtotime($dateAbsence));
        $dateGeneration = date('d/m/Y à H:i');
        $annee = date('Y');

        $html = '
        <html>
        <body style="font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0;">

          <table width="100%" style="border-bottom: 3px solid #059669; padding-bottom: 15px; margin-bottom: 20px;">
            <tr>
              <td width="70%">
                <h1 style="color: #059669; font-size: 22px; margin: 0;">PointagePro</h1>
                <p style="color: #666; font-size: 12px; margin: 3px 0;">Système de Gestion des Présences</p>
                <p style="color: #666; font-size: 11px; margin: 0;">Année : ' . $annee . '</p>
              </td>
              <td width="30%" style="text-align: right;">
                <p style="font-size: 11px; color: #666; margin: 0;">Genere le : ' . $dateGeneration . '</p>
              </td>
            </tr>
          </table>

          <div style="text-align: center; margin: 20px 0 30px 0;">
            <h2 style="font-size: 18px; color: #1a1a2e; text-transform: uppercase; letter-spacing: 2px; border: 2px solid #059669; display: inline-block; padding: 8px 24px; border-radius: 6px;">
              JUSTIFICATIF D\'ABSENCE
            </h2>
          </div>

          <div style="background: #f8f9fa; border-left: 4px solid #059669; padding: 15px 20px; margin-bottom: 25px; border-radius: 0 8px 8px 0;">
            <h3 style="font-size: 13px; color: #059669; margin: 0 0 10px 0; text-transform: uppercase;">Informations de l\'étudiant</h3>
            <table width="100%">
              <tr>
                <td style="font-size: 13px; padding: 3px 0;"><strong>Nom complet :</strong></td>
                <td style="font-size: 13px; padding: 3px 0;">' . htmlspecialchars($nom) . '</td>
              </tr>
              <tr>
                <td style="font-size: 13px; padding: 3px 0;"><strong>Département :</strong></td>
                <td style="font-size: 13px; padding: 3px 0;">' . htmlspecialchars($dept) . '</td>
              </tr>
              <tr>
                <td style="font-size: 13px; padding: 3px 0;"><strong>Date d\'absence :</strong></td>
                <td style="font-size: 13px; padding: 3px 0; color: #e53e3e;"><strong>' . $dateFormatee . '</strong></td>
              </tr>
            </table>
          </div>

          <div style="margin-bottom: 25px;">
            <h3 style="font-size: 13px; color: #333; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 6px; margin-bottom: 10px;">Motif de l\'absence</h3>
            <p style="font-size: 13px; background: #fff8e1; padding: 12px 16px; border-radius: 6px; border: 1px solid #ffe082;">
              <strong>' . htmlspecialchars($motif) . '</strong>
            </p>
          </div>';

        if (!empty($details)) {
            $html .= '
          <div style="margin-bottom: 25px;">
            <h3 style="font-size: 13px; color: #333; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 6px; margin-bottom: 10px;">Détails complémentaires</h3>
            <p style="font-size: 13px; line-height: 1.6; padding: 12px 16px; background: #f8f9fa; border-radius: 6px;">
              ' . nl2br(htmlspecialchars($details)) . '
            </p>
          </div>';
        }

        $statutBadge = '';
        if ($pointage) {
            $statutBadge = '
          <div style="margin-bottom: 25px; background: #fff3cd; border: 1px solid #ffc107; padding: 12px 16px; border-radius: 6px;">
            <p style="font-size: 12px; color: #856404; margin: 0;">
              <strong>Statut enregistré :</strong> 
              ' . strtoupper($pointage['type']) . ' — ' . $dateFormatee . '
            </p>
          </div>';
        }
        $html .= $statutBadge;

        $html .= '
          <table width="100%" style="margin-top: 40px;">
            <tr>
              <td width="50%" style="font-size: 12px; color: #666;">
                <p>L\'étudiant(e) soussigné(e) certifie</p>
                <p>l\'exactitude des informations ci-dessus.</p>
                <br><br>
                <p style="border-top: 1px solid #999; width: 180px; padding-top: 5px;">Signature de l\'étudiant</p>
              </td>
              <td width="50%" style="text-align: right; font-size: 12px; color: #666;">
                <p>Visa de l\'administration</p>
                <br><br><br>
                <p style="border-top: 1px solid #999; width: 180px; padding-top: 5px; display: inline-block;">Cachet et signature</p>
              </td>
            </tr>
          </table>

          <div style="margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px; text-align: center;">
            <p style="font-size: 10px; color: #999;">Document généré automatiquement par PointagePro — ' . $dateGeneration . '</p>
          </div>

        </body>
        </html>';

        if (!$this->leaveModel->existePourDate($userId, $dateAbsence)) {
            $this->leaveModel->creer($userId, $motif, $dateAbsence, $dateAbsence, $details);
        }

        try {
            $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt = $this->db->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, ip) VALUES (?, 'justificatif', ?, ?, ?)");
            $stmt->execute([$userId, $nom, $dateAbsence, $ip]);
        } catch (\Exception $e) {}

        try {
            $mpdf = new \Mpdf\Mpdf([
                'margin_top'    => 15,
                'margin_bottom' => 15,
                'margin_left'   => 20,
                'margin_right'  => 20,
            ]);

            $mpdf->SetTitle('Justificatif d\'absence - ' . $nom);
            $mpdf->SetAuthor('PointagePro');
            $mpdf->WriteHTML($html);

            $nomFichier = 'justificatif_' . str_replace(' ', '_', strtolower($nom)) . '_' . $dateAbsence . '.pdf';
            $mpdf->Output($nomFichier, 'D');
            exit;

        } catch (\Exception $e) {
            error_log('mPDF error: ' . $e->getMessage());
            header('Location: index.php?route=etudiant/dashboard&erreur=pdf_failed');
            exit;
        }
    }
}