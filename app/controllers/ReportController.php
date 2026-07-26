<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Leave.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportController
{
  private PDO $db;
  private User $userModel;
  private Attendance $attendanceModel;
  private Leave $leaveModel;

  public function __construct()
  {
    $database = new Database();
    $this->db = $database->getConnection();
    $this->userModel = new User();
    $this->attendanceModel = new Attendance();
    $this->leaveModel = new Leave();
  }

  public function rapportMensuelPdf(): void
  {
    $mois    = $_GET['mois'] ?? date('Y-m');
    $annee   = substr($mois, 0, 4);
    $numMois = substr($mois, 5, 2);

    $nomMois = [
      '01' => 'Janvier',
      '02' => 'Fevrier',
      '03' => 'Mars',
      '04' => 'Avril',
      '05' => 'Mai',
      '06' => 'Juin',
      '07' => 'Juillet',
      '08' => 'Aout',
      '09' => 'Septembre',
      '10' => 'Octobre',
      '11' => 'Novembre',
      '12' => 'Decembre'
    ];

    $totalEtudiants = $this->userModel->compterEtudiantsActifs();
    $totalPresents  = $this->attendanceModel->compterPourMoisGlobal($mois, 'present');
    $totalRetards   = $this->attendanceModel->compterPourMoisGlobal($mois, 'retard');
    $totalAbsences  = $this->attendanceModel->compterPourMoisGlobal($mois, 'absence');

    $presences = $this->attendanceModel->listePourMois($mois);

    $dateGen    = date('d/m/Y a H:i');
    $titresMois = ($nomMois[$numMois] ?? $numMois) . ' ' . $annee;

    // ----------------------------------------------------------------
    // Section tableau : affiché seulement si des données existent
    // ----------------------------------------------------------------
    if (!empty($presences)) {
      $tableauHtml = '
          <h3 style="font-size:14px;color:#333;border-bottom:1px solid #ddd;padding-bottom:6px;margin-bottom:10px">
            Detail des Presences — ' . $titresMois . '
          </h3>
          <table width="100%" style="border-collapse:collapse;font-size:12px">
            <thead>
              <tr style="background:#059669;color:white">
                <th style="padding:8px;text-align:left">Etudiant</th>
                <th style="padding:8px;text-align:left">Departement</th>
                <th style="padding:8px;text-align:left">Date</th>
                <th style="padding:8px;text-align:left">Arrivee</th>
                <th style="padding:8px;text-align:left">Depart</th>
                <th style="padding:8px;text-align:left">Statut</th>
              </tr>
            </thead>
            <tbody>';

      foreach ($presences as $i => $p) {
        $bg    = ($i % 2 === 0) ? '#f8f9fa' : '#ffffff';
        $statutColor = ['present' => '#059669', 'retard' => '#d97706', 'absence' => '#dc2626'];
        $color = $statutColor[$p['type']] ?? '#333';
        $tableauHtml .= '
              <tr style="background:' . $bg . '">
                <td style="padding:7px 8px;border-bottom:1px solid #eee">' . htmlspecialchars($p['nom'] ?? $p['email']) . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;color:#666">' . htmlspecialchars($p['department'] ?? '-') . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace">' . $p['date'] . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace;color:#059669">' . ($p['check_in'] ? substr($p['check_in'], 0, 5) : '--') . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace;color:#2563eb">' . ($p['check_out'] ? substr($p['check_out'], 0, 5) : '--') . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;color:' . $color . ';font-weight:bold;text-transform:uppercase">' . strtoupper($p['type']) . '</td>
              </tr>';
      }

      $tableauHtml .= '
            </tbody>
          </table>';
    } else {
      // Aucune présence : message sobre, pas de tableau ni d'entêtes
      $tableauHtml = '
          <div style="margin:30px 0;padding:20px;background:#f8f9fa;border-radius:8px;text-align:center;border:1px solid #e2e8f0">
            <p style="color:#666;font-size:13px;margin:0">
              Aucune presence enregistree pour ' . $titresMois . '.
            </p>
          </div>';
    }

    $html = '
        <html><body style="font-family:Arial,sans-serif;color:#333">
          <table width="100%" style="border-bottom:3px solid #059669;padding-bottom:15px;margin-bottom:20px">
            <tr>
              <td><h1 style="color:#059669;margin:0">PointagePro</h1>
                  <p style="color:#666;font-size:12px;margin:3px 0">Systeme de Gestion des Presences</p></td>
              <td style="text-align:right"><p style="font-size:11px;color:#666">Genere le : ' . $dateGen . '</p></td>
            </tr>
          </table>

          <div style="text-align:center;margin:20px 0 30px">
            <h2 style="font-size:18px;color:#1a1a2e;text-transform:uppercase;border:2px solid #059669;display:inline-block;padding:8px 24px;border-radius:6px">
              RAPPORT MENSUEL — ' . $titresMois . '
            </h2>
          </div>

          <div style="display:table;width:100%;margin-bottom:25px">
            <div style="display:table-row">
              <div style="display:table-cell;width:25%;padding:10px;background:#0d2a1e;border-radius:8px;text-align:center;margin:5px">
                <p style="color:#34d399;font-size:28px;font-weight:bold;margin:0">' . $totalEtudiants . '</p>
                <p style="color:#94a3b8;font-size:12px;margin:4px 0">Total Etudiants</p>
              </div>
            </div>
          </div>

          <table width="100%" style="margin-bottom:25px;border-collapse:collapse">
            <tr>
              <td width="25%" style="padding:12px;background:#0d2a1e;border-radius:8px;text-align:center">
                <p style="color:#34d399;font-size:24px;font-weight:bold;margin:0">' . $totalPresents . '</p>
                <p style="color:#999;font-size:12px;margin:4px 0">Presences</p>
              </td>
              <td width="5%"></td>
              <td width="25%" style="padding:12px;background:#2d2006;border-radius:8px;text-align:center">
                <p style="color:#fbbf24;font-size:24px;font-weight:bold;margin:0">' . $totalRetards . '</p>
                <p style="color:#999;font-size:12px;margin:4px 0">Retards</p>
              </td>
              <td width="5%"></td>
              <td width="25%" style="padding:12px;background:#2d1414;border-radius:8px;text-align:center">
                <p style="color:#f87171;font-size:24px;font-weight:bold;margin:0">' . $totalAbsences . '</p>
                <p style="color:#999;font-size:12px;margin:4px 0">Absences</p>
              </td>
              <td width="5%"></td>
              <td width="25%" style="padding:12px;background:#1a2333;border-radius:8px;text-align:center">
                <p style="color:#60a5fa;font-size:24px;font-weight:bold;margin:0">' . $totalEtudiants . '</p>
                <p style="color:#999;font-size:12px;margin:4px 0">Etudiants</p>
              </td>
            </tr>
          </table>

          ' . $tableauHtml . '

          <div style="margin-top:30px;border-top:1px solid #ddd;padding-top:10px;text-align:center">
            <p style="font-size:10px;color:#999">Document genere automatiquement par PointagePro — ' . $dateGen . '</p>
          </div>
        </body></html>';

    try {
      $mpdf = new \Mpdf\Mpdf(['margin_top' => 15, 'margin_bottom' => 15, 'margin_left' => 15, 'margin_right' => 15]);
      $mpdf->SetTitle('Rapport Mensuel - ' . $titresMois);
      $mpdf->SetAuthor('PointagePro');
      $mpdf->WriteHTML($html);
      $mpdf->Output('rapport_mensuel_' . $mois . '.pdf', 'D');
      exit;
    } catch (\Exception $e) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Erreur PDF: ' . $e->getMessage()]);
      exit;
    }
  }

  public function fichePresenceExcel(): void
  {
    $date = $_GET['date'] ?? '';
    $mois = $_GET['mois'] ?? '';
    $dept = $_GET['dept'] ?? '';
    $statut = $_GET['statut'] ?? '';

    if (!empty($date)) {
      $presences = $this->attendanceModel->presencesParDate($date, $dept ?: null, $statut ?: null);
      $titre = 'PRESENCES_' . $date;
      $sousTitre = 'Date : ' . $date;
    } else {
      $mois = $mois ?: date('Y-m');
      $presences = $this->attendanceModel->listePourMois($mois, $dept ?: null);
      $titre = 'PRESENCES_' . $mois;
      $sousTitre = 'Mois : ' . $mois;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Presences');

    $headerStyle = [
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
      'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '047857']]],
    ];

    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'POINTAGEPRO — FICHE DE PRESENCE — ' . $titre);
    $sheet->mergeCells('A2:G2');
    $sheet->setCellValue('A2', $sousTitre);
    $sheet->getStyle('A2')->applyFromArray(['font' => ['italic' => true, 'size' => 11, 'color' => ['rgb' => '666666']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
    $sheet->getRowDimension(2)->setRowHeight(18);
    $sheet->getStyle('A1')->applyFromArray([
      'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '059669']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(30);

    $headers = ['Nom', 'Email', 'Departement', 'Date', 'Arrivee', 'Depart', 'Statut'];
    foreach ($headers as $i => $h) {
      $col = chr(65 + $i);
      $sheet->setCellValue($col . '2', $h);
      $sheet->getStyle($col . '2')->applyFromArray($headerStyle);
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getRowDimension(2)->setRowHeight(20);

    if (!empty($presences)) {
      $row = 3;
      foreach ($presences as $p) {
        $sheet->setCellValue('A' . $row, $p['nom'] ?? $p['email']);
        $sheet->setCellValue('B' . $row, $p['email']);
        $sheet->setCellValue('C' . $row, $p['department'] ?? '-');
        $sheet->setCellValue('D' . $row, $p['date']);
        $sheet->setCellValue('E' . $row, $p['check_in'] ? substr($p['check_in'], 0, 5) : '--');
        $sheet->setCellValue('F' . $row, $p['check_out'] ? substr($p['check_out'], 0, 5) : '--');
        $sheet->setCellValue('G' . $row, strtoupper($p['type']));
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
          'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        $row++;
      }
      $sheet->setCellValue('A' . $row, 'TOTAL : ' . count($presences) . ' enregistrements');
      $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '059669']]]);
    } else {
      // Aucune donnée : message dans la feuille, pas de lignes vides
      $sheet->mergeCells('A3:G3');
      $sheet->setCellValue('A3', 'Aucune presence enregistree pour ce mois.');
      $sheet->getStyle('A3')->applyFromArray([
        'font'      => ['italic' => true, 'color' => ['rgb' => '666666']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ]);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="fiche_presences_' . $titre . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }

  public function rapportPresencesPdf(): void
  {
    $date = $_GET['date'] ?? '';
    $dept = $_GET['dept'] ?? '';
    $statut = $_GET['statut'] ?? '';

    if (!empty($date)) {
      $presences = $this->attendanceModel->presencesParDate($date, $dept ?: null, $statut ?: null);
      $title = 'Presences du ' . $date;
      $subtitle = 'Filtre: ' . ($dept ? 'Departement=' . $dept : 'Tous departements') . ($statut ? ', Statut=' . $statut : '');
    } else {
      $mois = $_GET['mois'] ?? date('Y-m');
      $presences = $this->attendanceModel->listePourMois($mois, $dept ?: null);
      $title = 'Presences du mois ' . $mois;
      $subtitle = 'Departement: ' . ($dept ?: 'Tous');
    }

    $dateGen = date('d/m/Y a H:i');

    if (!empty($statut)) {
      $statutLabel = strtoupper($statut);
    } else {
      $statutLabel = 'Tous';
    }

    $html = '<html><body style="font-family:Arial,sans-serif;color:#333">'
      . '<table width="100%" style="border-bottom:3px solid #059669;padding-bottom:15px;margin-bottom:20px"><tr>'
      . '<td><h1 style="color:#059669;margin:0">PointagePro</h1><p style="color:#666;font-size:12px;margin:3px 0">Export presence</p></td>'
      . '<td style="text-align:right"><p style="font-size:11px;color:#666">Genere le : ' . $dateGen . '</p></td>'
      . '</tr></table>'
      . '<h2 style="font-size:16px;color:#1a1a2e;margin-bottom:5px">' . htmlspecialchars($title) . '</h2>'
      . '<p style="font-size:12px;color:#666;margin-top:0;margin-bottom:20px">' . htmlspecialchars($subtitle) . '</p>';

    if (!empty($presences)) {
      $html .= '<table width="100%" style="border-collapse:collapse;font-size:12px">'
        . '<thead><tr style="background:#059669;color:white">'
        . '<th style="padding:8px;text-align:left">Etudiant</th>'
        . '<th style="padding:8px;text-align:left">Departement</th>'
        . '<th style="padding:8px;text-align:left">Date</th>'
        . '<th style="padding:8px;text-align:left">Arrivee</th>'
        . '<th style="padding:8px;text-align:left">Depart</th>'
        . '<th style="padding:8px;text-align:left">Statut</th>'
        . '</tr></thead><tbody>';
      foreach ($presences as $i => $p) {
        $bg = ($i % 2 === 0) ? '#f8f9fa' : '#ffffff';
        $colorMap = ['present' => '#059669', 'retard' => '#d97706', 'absence' => '#dc2626'];
        $color = $colorMap[$p['type']] ?? '#333';
        $html .= '<tr style="background:' . $bg . '">'
          . '<td style="padding:7px 8px;border-bottom:1px solid #eee">' . htmlspecialchars($p['nom'] ?? $p['email']) . '</td>'
          . '<td style="padding:7px 8px;border-bottom:1px solid #eee;color:#666">' . htmlspecialchars($p['department'] ?? '-') . '</td>'
          . '<td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace">' . $p['date'] . '</td>'
          . '<td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace;color:#059669">' . ($p['check_in'] ? substr($p['check_in'], 0, 5) : '--') . '</td>'
          . '<td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace;color:#2563eb">' . ($p['check_out'] ? substr($p['check_out'], 0, 5) : '--') . '</td>'
          . '<td style="padding:7px 8px;border-bottom:1px solid #eee;color:' . $color . ';font-weight:bold;text-transform:uppercase">' . strtoupper($p['type']) . '</td>'
          . '</tr>';
      }
      $html .= '</tbody></table>';
    } else {
      $html .= '<div style="margin:30px 0;padding:20px;background:#f8f9fa;border-radius:8px;text-align:center;border:1px solid #e2e8f0">'
        . '<p style="color:#666;font-size:13px;margin:0">Aucune presence enregistree.</p>'
        . '</div>';
    }

    $html .= '<div style="margin-top:30px;border-top:1px solid #ddd;padding-top:10px;text-align:center">'
      . '<p style="font-size:10px;color:#999">Document genere automatiquement par PointagePro — ' . $dateGen . '</p>'
      . '</div></body></html>';

    try {
      $mpdf = new \Mpdf\Mpdf(['margin_top' => 15, 'margin_bottom' => 15, 'margin_left' => 15, 'margin_right' => 15]);
      $mpdf->SetTitle('Presences_' . str_replace(' ', '_', $title));
      $mpdf->WriteHTML($html);
      $filename = 'presences_' . (!empty($date) ? $date : str_replace('-', '_', $mois)) . '.pdf';
      $mpdf->Output($filename, 'D');
      exit;
    } catch (\Exception $e) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Erreur PDF: ' . $e->getMessage()]);
      exit;
    }
  }

  public function rapportCongesPdf(): void
  {
    $mois = $_GET['mois'] ?? date('Y-m');

    $conges  = $this->leaveModel->pourMois($mois);
    $dateGen = date('d/m/Y a H:i');

    // ----------------------------------------------------------------
    // Section tableau congés : affiché seulement si des données existent
    // ----------------------------------------------------------------
    if (!empty($conges)) {
      $typeLabel   = ['maladie' => 'Maladie', 'conge_annuel' => 'Conge annuel', 'urgence' => 'Urgence', 'autre' => 'Autre'];
      $statutColor = ['approuve' => '#059669', 'refuse' => '#dc2626', 'en_attente' => '#d97706'];

      $tableauCongesHtml = '
          <table width="100%" style="border-collapse:collapse;font-size:12px">
            <thead>
              <tr style="background:#059669;color:white">
                <th style="padding:8px;text-align:left">Etudiant</th>
                <th style="padding:8px;text-align:left">Departement</th>
                <th style="padding:8px;text-align:left">Type</th>
                <th style="padding:8px;text-align:left">Du</th>
                <th style="padding:8px;text-align:left">Au</th>
                <th style="padding:8px;text-align:left">Motif</th>
                <th style="padding:8px;text-align:left">Statut</th>
                <th style="padding:8px;text-align:left">Valide par</th>
              </tr>
            </thead>
            <tbody>';

      foreach ($conges as $i => $c) {
        $bg    = ($i % 2 === 0) ? '#f8f9fa' : '#ffffff';
        $color = $statutColor[$c['status']] ?? '#333';
        $tableauCongesHtml .= '
              <tr style="background:' . $bg . '">
                <td style="padding:7px 8px;border-bottom:1px solid #eee">' . htmlspecialchars($c['nom']) . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;color:#666">' . htmlspecialchars($c['department'] ?? '-') . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee">' . ($typeLabel[$c['type']] ?? $c['type']) . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace">' . $c['start_date'] . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;font-family:monospace">' . $c['end_date'] . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;color:#666;font-size:11px">' . htmlspecialchars($c['reason'] ?? '-') . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;color:' . $color . ';font-weight:bold;text-transform:uppercase">' . strtoupper(str_replace('_', ' ', $c['status'])) . '</td>
                <td style="padding:7px 8px;border-bottom:1px solid #eee;color:#666">' . htmlspecialchars($c['reviewer_nom'] ?? '-') . '</td>
              </tr>';
      }

      $tableauCongesHtml .= '
            </tbody>
          </table>
          <p style="margin-top:15px;font-size:12px;color:#666">Total : ' . count($conges) . ' demande(s)</p>';
    } else {
      $tableauCongesHtml = '
          <div style="margin:30px 0;padding:20px;background:#f8f9fa;border-radius:8px;text-align:center;border:1px solid #e2e8f0">
            <p style="color:#666;font-size:13px;margin:0">
              Aucune demande d\'absence enregistree pour ce mois.
            </p>
          </div>';
    }

    $html = '
        <html><body style="font-family:Arial,sans-serif;color:#333">
          <table width="100%" style="border-bottom:3px solid #059669;padding-bottom:15px;margin-bottom:20px">
            <tr>
              <td><h1 style="color:#059669;margin:0">PointagePro</h1>
                  <p style="color:#666;font-size:12px">Systeme de Gestion des Presences</p></td>
              <td style="text-align:right"><p style="font-size:11px;color:#666">Genere le : ' . $dateGen . '</p></td>
            </tr>
          </table>

          <div style="text-align:center;margin:20px 0 30px">
            <h2 style="font-size:18px;color:#1a1a2e;text-transform:uppercase;border:2px solid #059669;display:inline-block;padding:8px 24px;border-radius:6px">
              RAPPORT CONGES — ' . $mois . '
            </h2>
          </div>

          ' . $tableauCongesHtml . '

          <div style="margin-top:30px;border-top:1px solid #ddd;padding-top:10px;text-align:center">
            <p style="font-size:10px;color:#999">Document genere par PointagePro — ' . $dateGen . '</p>
          </div>
        </body></html>';

    try {
      $mpdf = new \Mpdf\Mpdf(['margin_top' => 15, 'margin_bottom' => 15, 'margin_left' => 15, 'margin_right' => 15]);
      $mpdf->SetTitle('Rapport Conges - ' . $mois);
      $mpdf->WriteHTML($html);
      $mpdf->Output('rapport_conges_' . $mois . '.pdf', 'D');
      exit;
    } catch (\Exception $e) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Erreur PDF: ' . $e->getMessage()]);
      exit;
    }
  }
}
