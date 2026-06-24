<?php
$nom             = $_SESSION['user']['nom']   ?? 'Etudiant';
$email           = $_SESSION['user']['email'] ?? '';
$role            = $_SESSION['user']['role']  ?? 'etudiant';
$dept            = $_SESSION['user']['department'] ?? '';
$qrToken         = $qrToken         ?? null;
$pointageAujourdhui = $pointageAujourdhui ?? null;
$presents        = $presents        ?? 0;
$retards         = $retards         ?? 0;
$absences        = $absences        ?? 0;
$totalJours      = $totalJours      ?? 0;
$historique      = $historique      ?? [];
$conges          = $conges          ?? [];

// Message de pointage après scan du QR mural
$scanMsg  = $_GET['scan_msg']  ?? '';
$scanType = $_GET['scan_type'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PointagePro — Mon Espace</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Space Grotesk',sans-serif;background:#0a0e1a;color:#e2e8f0;min-height:100vh}
    .mono{font-family:'JetBrains Mono',monospace}
    .card{background:#0f1629;border:1px solid #1e2a4a;border-radius:16px}
    .badge-present{background:#0d2a1e;color:#34d399;border:1px solid #064e3b}
    .badge-absent{background:#2d1414;color:#f87171;border:1px solid #7f1d1d}
    .badge-retard{background:#2d2006;color:#fbbf24;border:1px solid #78350f}
    .badge-pending{background:#1a2333;color:#60a5fa;border:1px solid #1e3a5f}
    .badge-approuve{background:#0d2a1e;color:#34d399;border:1px solid #064e3b}
    .badge-refuse{background:#2d1414;color:#f87171;border:1px solid #7f1d1d}
    @keyframes pulse-ring{0%{transform:scale(.95);box-shadow:0 0 0 0 rgba(52,211,153,.4)}70%{transform:scale(1);box-shadow:0 0 0 10px rgba(52,211,153,0)}100%{transform:scale(.95);box-shadow:0 0 0 0 rgba(52,211,153,0)}}
    @keyframes slide-in{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    @keyframes scan-flash{0%{opacity:0;transform:scale(.97)}40%{opacity:1;transform:scale(1.01)}100%{opacity:1;transform:scale(1)}}
    .animate-in{animation:slide-in .4s ease forwards}
    .scan-confirm{animation:scan-flash .5s ease forwards}
    .pulse-dot{animation:pulse-ring 2s ease-in-out infinite}
    .stat-accent-green{border-left:3px solid #34d399}
    .stat-accent-red{border-left:3px solid #f87171}
    .stat-accent-yellow{border-left:3px solid #fbbf24}
    .stat-accent-blue{border-left:3px solid #60a5fa}
    input,select,textarea{background:#0a0e1a;border:1px solid #1e2a4a;border-radius:10px;color:#e2e8f0;padding:10px 14px;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s;width:100%}
    input:focus,select:focus,textarea:focus{border-color:#34d399}
    .btn-primary{background:#059669;color:white;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-primary:hover{background:#047857;transform:translateY(-1px)}
    .btn-secondary{background:#1e2a4a;color:#e2e8f0;border:1px solid #2d3f6a;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-secondary:hover{background:#253354}
    .btn-danger{background:#7f1d1d;color:#fca5a5;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-danger:hover{background:#991b1b}
    .modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;display:flex;align-items:center;justify-content:center}
    .modal{background:#0f1629;border:1px solid #1e2a4a;border-radius:20px;padding:32px;width:480px;max-width:90vw;max-height:85vh;overflow-y:auto}
    .toast{position:fixed;bottom:24px;right:24px;background:#0f1629;border:1px solid #1e2a4a;border-radius:12px;padding:14px 18px;z-index:9999;animation:slide-in .3s ease;display:flex;align-items:center;gap:10px;font-size:14px;box-shadow:0 8px 32px rgba(0,0,0,.4)}
    ::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:#0a0e1a}::-webkit-scrollbar-thumb{background:#1e2a4a;border-radius:3px}
    .view{display:none}
    .view.active{display:block;animation:slide-in .4s ease forwards}
    .nav-item{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:10px;cursor:pointer;transition:all .2s;color:#94a3b8;font-size:14px;font-weight:500;text-decoration:none}
    .nav-item:hover{background:#1e2a4a;color:#e2e8f0}
    .nav-item.active{background:#1a3a5c;color:#34d399}
    .nav-item svg{width:18px;height:18px;flex-shrink:0}
    .sidebar{width:240px;background:#0f1629;border-right:1px solid #1e2a4a;height:100vh;position:fixed;left:0;top:0;z-index:100;transition:transform .3s ease;display:flex;flex-direction:column}
    .sidebar.collapsed{transform:translateX(-240px)}
    .main-content{margin-left:240px;min-height:100vh;transition:margin .3s ease}
    .main-content.expanded{margin-left:0}
    @media (max-width: 768px) {
      .sidebar{transform:translateX(-240px)}
      .sidebar.mobile-open{transform:translateX(0);box-shadow:4px 0 20px rgba(0,0,0,.5)}
      .main-content{margin-left:0 !important}
      .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}
      .sidebar-overlay.active{display:block}
      #page-title{font-size:14px}
      .sticky .text-xs.bg-emerald-900\/50{display:none}
      .grid.grid-cols-2.lg\:grid-cols-4{grid-template-columns:repeat(2,1fr);gap:10px}
      .card.p-5{padding:12px}
      .text-3xl{font-size:1.4rem}
      .grid.grid-cols-1.lg\:grid-cols-3{grid-template-columns:1fr}
      .lg\:col-span-2{grid-column:span 1}
      .p-6{padding:14px}
      .p-5{padding:12px}
      .p-8{padding:20px}
      .max-w-lg{max-width:100%}
      .max-w-sm{max-width:100%}
      .modal{width:95vw;padding:20px;max-height:90vh}
      .text-xl{font-size:16px}
      .flex.gap-3 .btn-secondary,.flex.gap-3 .btn-primary{padding:8px 12px;font-size:13px}
      .grid.grid-cols-1.sm\:grid-cols-2.xl\:grid-cols-3{grid-template-columns:1fr}
    }
    @media (max-width: 480px) {
      #page-title{font-size:13px}
      .grid.grid-cols-2.lg\:grid-cols-4{gap:8px}
    }
  </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebarMobile()"></div>

<div class="sidebar" id="sidebar">
  <div class="p-5 border-b border-[#1e2a4a]">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-emerald-600 rounded-xl flex items-center justify-center">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" class="w-5 h-5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <p class="font-bold text-[15px] text-white">PointagePro</p>
        <p class="text-[11px] text-emerald-400 mono">v1.0 &mdash; Etudiant</p>
      </div>
    </div>
  </div>
  <div class="p-3 mt-2 flex-1">
    <p class="text-[11px] uppercase tracking-widest text-slate-600 px-3 mb-2">Navigation</p>
    <nav id="main-nav" class="space-y-1">
      <div class="nav-item active" data-view="accueil">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Mon Tableau de bord
      </div>
      <div class="nav-item" data-view="pointer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Me Pointer
      </div>
      <div class="nav-item" data-view="monqr">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><line x1="14" y1="14" x2="14" y2="21"/><line x1="14" y1="14" x2="21" y2="14"/></svg>
        Mon QR Code
      </div>
      <div class="nav-item" data-view="historique">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Mes Pointages
      </div>
      <div class="nav-item" data-view="absences">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Mes Absences
      </div>
      <div class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Justificatif PDF
      </div>
    </nav>
    <div class="mt-6">
      <p class="text-[11px] uppercase tracking-widest text-slate-600 px-3 mb-2">Compte</p>
      <div class="nav-item" style="cursor:pointer" onclick="window.location.href='index.php?route=logout'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Deconnexion
      </div>
    </div>
  </div>
  <div class="p-4 border-t border-[#1e2a4a]">
    <div class="flex items-center gap-3">
      <div style="width:36px;height:36px;border-radius:50%;background:#064e3b;color:#34d399;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0"><?= strtoupper(substr($nom, 0, 2)) ?></div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-600 text-white truncate"><?= htmlspecialchars($nom) ?></p>
        <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($dept) ?></p>
      </div>
      <div class="w-2 h-2 bg-emerald-400 rounded-full pulse-dot"></div>
    </div>
  </div>
</div>

<div class="main-content" id="main-content">
  <div class="sticky top-0 z-50 bg-[#0a0e1a]/90 backdrop-blur border-b border-[#1e2a4a] px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-[#1e2a4a] transition-colors">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div id="page-title" class="text-lg font-700 text-white">Mon Tableau de bord</div>
    </div>
    <div class="flex items-center gap-3">
      <div class="mono text-xs text-slate-400" id="clock-display">--:--:--</div>
      <span class="text-xs bg-emerald-900/50 text-emerald-400 border border-emerald-800 px-3 py-1 rounded-full"><?= htmlspecialchars($dept) ?></span>
    </div>
  </div>

  <div class="p-6">

    <!-- ============================================ -->
    <!-- BANDEAU CONFIRMATION POINTAGE POST-SCAN QR   -->
    <!-- Affiché si l'étudiant arrive depuis scan/pointer -->
    <!-- ============================================ -->
    <?php if (!empty($scanMsg)): ?>
    <?php
      $scanStyles = [
        'scan_ok'   => ['border'=>'#34d399','bg'=>'#0a1f15','icon'=>'#34d399','emoji'=>'✅','iconBg'=>'#064e3b'],
        'scan_info' => ['border'=>'#60a5fa','bg'=>'#0a1020','icon'=>'#60a5fa','emoji'=>'ℹ️','iconBg'=>'#1e3a5f'],
        'erreur'    => ['border'=>'#f87171','bg'=>'#1a0808','icon'=>'#f87171','emoji'=>'⚠️','iconBg'=>'#7f1d1d'],
      ];
      $s = $scanStyles[$scanType] ?? $scanStyles['scan_info'];
    ?>
    <div class="scan-confirm mb-5 p-4 rounded-2xl flex items-center gap-4"
         style="background:<?= $s['bg'] ?>;border:1px solid <?= $s['border'] ?>">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 text-xl"
           style="background:<?= $s['iconBg'] ?>">
        <?= $s['emoji'] ?>
      </div>
      <div>
        <p class="font-600 text-white text-sm"><?= htmlspecialchars($scanMsg) ?></p>
        <p class="text-xs mt-0.5" style="color:<?= $s['icon'] ?>">
          <?php if ($scanType === 'scan_ok'): ?>
            Pointage enregistré via QR Code de l'établissement
          <?php elseif ($scanType === 'erreur'): ?>
            Contactez l'administrateur si le problème persiste
          <?php else: ?>
            Informations de pointage
          <?php endif; ?>
        </p>
      </div>
      <button onclick="this.parentElement.remove()"
              style="margin-left:auto;background:none;border:none;color:#475569;cursor:pointer;font-size:18px;padding:4px 8px">×</button>
    </div>
    <?php endif; ?>
    <!-- ============================================ -->

    <!-- ACCUEIL -->
    <div id="view-accueil" class="view active animate-in">
      <div class="mb-6">
        <h2 class="text-xl font-600 text-white">Bonjour, <?= htmlspecialchars(explode(' ', $nom)[0]) ?> 👋</h2>
        <p class="text-sm text-slate-400 mt-1"><?= date('l d F Y') ?></p>
      </div>
      <?php if ($pointageAujourdhui): ?>
        <div class="card p-4 mb-6 flex items-center gap-4" style="border-left:3px solid #34d399">
          <div class="w-10 h-10 bg-emerald-900 rounded-xl flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5" class="w-5 h-5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <p class="font-600 text-white text-sm">Vous avez pointe aujourd'hui</p>
            <p class="text-xs text-slate-400">
              Arrivee : <span class="text-emerald-400 mono"><?= $pointageAujourdhui['check_in'] ? substr($pointageAujourdhui['check_in'], 0, 5) : '--' ?></span>
              &nbsp;&bull;&nbsp;
              Depart : <span class="text-blue-400 mono"><?= $pointageAujourdhui['check_out'] ? substr($pointageAujourdhui['check_out'], 0, 5) : 'Pas encore sorti' ?></span>
            </p>
          </div>
        </div>
      <?php else: ?>
        <div class="card p-4 mb-6 flex items-center gap-4" style="border-left:3px solid #f87171">
          <div class="w-10 h-10 bg-red-900 rounded-xl flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div>
            <p class="font-600 text-white text-sm">Vous n'avez pas encore pointe aujourd'hui</p>
            <p class="text-xs text-slate-400">Presentez votre QR Code a l'entree</p>
          </div>
        </div>
      <?php endif; ?>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-5 stat-accent-green">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Presents</p>
          <p class="text-3xl font-700 text-white mono"><?= $presents ?></p>
          <p class="text-xs text-emerald-400 mt-1">Ce mois</p>
        </div>
        <div class="card p-5 stat-accent-yellow">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Retards</p>
          <p class="text-3xl font-700 text-white mono"><?= $retards ?></p>
          <p class="text-xs text-yellow-400 mt-1">Ce mois</p>
        </div>
        <div class="card p-5 stat-accent-red">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Absences</p>
          <p class="text-3xl font-700 text-white mono"><?= $absences ?></p>
          <p class="text-xs text-red-400 mt-1">Ce mois</p>
        </div>
        <div class="card p-5 stat-accent-blue">
          <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Total Jours</p>
          <p class="text-3xl font-700 text-white mono"><?= $totalJours ?></p>
          <p class="text-xs text-blue-400 mt-1">Ce mois</p>
        </div>
      </div>
      <div class="grid grid-cols-1 gap-4">
        <div class="card">
          <div class="p-5 border-b border-[#1e2a4a] flex items-center justify-between">
            <p class="font-600 text-white">Derniers pointages</p>
            <button onclick="switchView('historique')" class="text-xs text-emerald-400">Voir tout</button>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
            <?php if (empty($historique)): ?>
              <p class="text-slate-500 text-sm text-center py-6 col-span-full">Aucun pointage</p>
            <?php else: ?>
              <?php foreach (array_slice($historique, 0, 5) as $h): ?>
              <?php
                $badges = ['present'=>'badge-present','retard'=>'badge-retard','absence'=>'badge-absent'];
                $labels = ['present'=>'Present','retard'=>'Retard','absence'=>'Absent'];
                $cls = $badges[$h['type']] ?? 'badge-pending';
                $lbl = $labels[$h['type']] ?? $h['type'];
              ?>
              <div class="card p-4 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                  <span class="mono text-sm text-white"><?= $h['date'] ?></span>
                  <span class="text-xs <?= $cls ?> px-2.5 py-1 rounded-full"><?= $lbl ?></span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-[#1e2a4a]">
                  <div class="text-xs"><span class="text-slate-600">Arrivee</span><br><span class="mono text-emerald-400"><?= $h['check_in'] ? substr($h['check_in'],0,5) : '--' ?></span></div>
                  <div class="text-xs text-right"><span class="text-slate-600">Depart</span><br><span class="mono text-blue-400"><?= $h['check_out'] ? substr($h['check_out'],0,5) : '--' ?></span></div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ME POINTER -->
    <div id="view-pointer" class="view animate-in">
      <div class="max-w-lg mx-auto space-y-4">
        <div id="geo-status" class="card p-4 flex items-center gap-3" style="border-left:3px solid #60a5fa">
          <div class="w-9 h-9 bg-blue-900/50 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" class="w-5 h-5"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
          </div>
          <div>
            <p id="geo-status-text" class="text-sm font-500 text-white">Verification de votre position...</p>
            <p id="geo-distance-text" class="text-xs text-slate-400 mt-0.5">En attente GPS</p>
          </div>
        </div>
        <div class="flex gap-2 border-b border-[#1e2a4a] pb-0">
          <button id="tab-btn-ecole" onclick="switchTab('ecole')" class="px-4 py-2 text-sm font-600 border-b-2 border-emerald-400 text-emerald-400 transition-colors">QR Code Ecole</button>
          <button id="tab-btn-perso" onclick="switchTab('perso')" class="px-4 py-2 text-sm font-500 border-b-2 border-transparent text-slate-400 transition-colors">Mon QR Code</button>
        </div>
        <div id="tab-ecole" class="card p-5">
          <p class="font-600 text-white mb-1">Scanner le QR Code de l'ecole</p>
          <p class="text-xs text-slate-400 mb-4">Scannez le QR Code affiche a l'entree de l'etablissement — l'arrivee ou le depart est detecte automatiquement</p>
          <div id="scan-status-ecole" class="mb-3 p-3 rounded-xl text-center text-sm font-500" style="background:#1a2333;color:#60a5fa;border:1px solid #1e3a5f">Camera non active</div>
          <div id="etu-qr-reader-ecole" class="mb-3 rounded-xl overflow-hidden" style="background:#000;min-height:60px"></div>
          <div class="grid grid-cols-2 gap-2">
            <button id="btn-start-ecole" onclick="demarrerScanEcole()" class="btn-primary py-2.5 text-sm">Activer Camera</button>
            <button id="btn-stop-ecole" onclick="arreterScanEcole()" class="btn-secondary py-2.5 text-sm" style="display:none">Arreter Camera</button>
          </div>
        </div>
        <div id="tab-perso" class="card p-5" style="display:none">
          <p class="font-600 text-white mb-1">Scanner mon QR Code personnel</p>
          <p class="text-xs text-slate-400 mb-4">Scannez votre propre QR Code — l'arrivee ou le depart est detecte automatiquement</p>
          <div id="scan-status-perso" class="mb-3 p-3 rounded-xl text-center text-sm font-500" style="background:#1a2333;color:#60a5fa;border:1px solid #1e3a5f">Camera non active</div>
          <div id="etu-qr-reader-perso" class="mb-3 rounded-xl overflow-hidden" style="background:#000;min-height:60px"></div>
          <div class="grid grid-cols-2 gap-2">
            <button id="btn-start-perso" onclick="demarrerScanPerso()" class="btn-primary py-2.5 text-sm">Activer Camera</button>
            <button id="btn-stop-perso" onclick="arreterScanPerso()" class="btn-secondary py-2.5 text-sm" style="display:none">Arreter Camera</button>
          </div>
        </div>
        <div class="p-3 rounded-xl text-center" style="background:#1a2333;border:1px solid #1e3a5f">
          <p class="text-xs text-slate-400">Perimetre autorise : <span class="text-blue-400 font-600" id="rayon-display">500 metres</span></p>
          <p class="text-xs text-slate-500 mt-0.5">Vous devez etre dans l'enceinte de l'etablissement</p>
        </div>
      </div>
    </div>

    <!-- MON QR CODE -->
    <div id="view-monqr" class="view animate-in">
      <div class="max-w-sm mx-auto">
        <div class="card p-8 text-center">
          <p class="font-700 text-white text-lg mb-1"><?= htmlspecialchars($nom) ?></p>
          <p class="text-sm text-slate-400 mb-6"><?= htmlspecialchars($dept) ?></p>
          <?php if ($qrToken): ?>
            <div id="qr-grand" class="inline-block p-4 bg-white rounded-2xl mb-4"></div>
            <p class="mono text-xs text-slate-500 break-all px-2 mb-4"><?= htmlspecialchars($qrToken) ?></p>
            <div class="flex gap-3">
              <button onclick="telechargerQR()" class="btn-primary flex-1 py-3">Telecharger QR</button>
              <button onclick="regenererMonQR()" class="btn-danger flex-1 py-3">Regenerer mon QR Code</button>
            </div>
          <?php else: ?>
            <p class="text-slate-500">Pas de QR Code disponible.</p>
            <p class="text-xs text-slate-600 mt-2">Contactez l'administrateur.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- HISTORIQUE -->
    <div id="view-historique" class="view animate-in">
      <div class="card">
        <div class="p-5 border-b border-[#1e2a4a]">
          <p class="font-600 text-white">Historique complet de mes pointages</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
          <?php if (empty($historique)): ?>
            <p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucun pointage enregistre</p>
          <?php else: ?>
            <?php foreach ($historique as $h): ?>
            <?php
              $duree = '--';
              if ($h['check_in'] && $h['check_out']) {
                $ci = explode(':', $h['check_in']);
                $co = explode(':', $h['check_out']);
                $mins = (intval($co[0])*60+intval($co[1]))-(intval($ci[0])*60+intval($ci[1]));
                $duree = floor($mins/60).'h'.str_pad($mins%60,2,'0',STR_PAD_LEFT);
              }
              $badges = ['present'=>'badge-present','retard'=>'badge-retard','absence'=>'badge-absent'];
              $labels = ['present'=>'Present','retard'=>'Retard','absence'=>'Absent'];
              $cls = $badges[$h['type']] ?? 'badge-pending';
              $lbl = $labels[$h['type']] ?? $h['type'];
            ?>
            <div class="card p-4 flex flex-col gap-3">
              <div class="flex items-center justify-between">
                <span class="mono text-sm text-white"><?= $h['date'] ?></span>
                <span class="text-xs <?= $cls ?> px-2.5 py-1 rounded-full"><?= $lbl ?></span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Duree</span>
                <span class="mono text-slate-400"><?= $duree ?></span>
              </div>
              <div class="flex items-center justify-between pt-2 border-t border-[#1e2a4a]">
                <div class="text-xs"><span class="text-slate-600">Arrivee</span><br><span class="mono text-emerald-400"><?= $h['check_in'] ? substr($h['check_in'],0,5) : '--' ?></span></div>
                <div class="text-xs text-right"><span class="text-slate-600">Depart</span><br><span class="mono text-blue-400"><?= $h['check_out'] ? substr($h['check_out'],0,5) : '--' ?></span></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ABSENCES -->
    <div id="view-absences" class="view animate-in">
      <div class="flex justify-end mb-4">
        <button onclick="ouvrirFormulaireAbsence()" class="btn-primary text-sm px-4 py-2">+ Nouvelle demande</button>
      </div>
      <div class="card mb-5">
        <div class="p-5 border-b border-[#1e2a4a]">
          <p class="font-600 text-white">Mes Demandes d'Absence</p>
          <p class="text-xs text-slate-400 mt-1">Absence passee a justifier ou absence future a declarer</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
          <?php if (empty($conges)): ?>
            <p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucune demande</p>
          <?php else: ?>
            <?php foreach ($conges as $c): ?>
            <?php
              $badgesC = ['en_attente'=>'badge-pending','approuve'=>'badge-present','refuse'=>'badge-absent'];
              $labelsC = ['en_attente'=>'En attente','approuve'=>'Approuve','refuse'=>'Refuse'];
              $clsC = $badgesC[$c['status']] ?? 'badge-pending';
              $lblC = $labelsC[$c['status']] ?? $c['status'];
              $typeLabel = ['maladie'=>'Maladie','conge_annuel'=>'Conge','urgence'=>'Urgence','autre'=>'Autre','absence_passee'=>'Absence passee','absence_future'=>'Absence future'];
              $today = date('Y-m-d');
              $dateAbs = $c['date_absence'] ?? $c['start_date'];
              $estPassee = $c['start_date'] <= $today;
              $absType = $estPassee ? 'Passee' : 'Future';
            ?>
            <div class="card p-4 flex flex-col gap-3">
              <div class="flex items-center justify-between gap-2">
                <div>
                  <span class="mono text-sm text-white"><?= $dateAbs ?></span>
                  <span class="text-[10px] <?= $estPassee ? 'badge-absent' : 'badge-pending' ?> px-1.5 py-0.5 rounded ml-1"><?= $absType ?></span>
                </div>
                <span class="text-xs <?= $clsC ?> px-2.5 py-1 rounded-full"><?= $lblC ?></span>
              </div>
              <div class="text-xs text-slate-400"><?= $typeLabel[$c['type']] ?? $c['type'] ?></div>
              <?php if (!empty($c['reason'])): ?>
                <p class="text-xs text-slate-400 line-clamp-2"><?= htmlspecialchars($c['reason']) ?></p>
              <?php endif; ?>
              <div class="pt-2 border-t border-[#1e2a4a]">
                <?php if (!empty($c['document'])): ?>
                  <a href="uploads/justificatifs/<?= htmlspecialchars($c['document']) ?>" target="_blank" class="text-xs text-emerald-400 underline">Voir document</a>
                <?php else: ?>
                  <span class="text-xs text-slate-600">Aucun document</span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <div id="form-absence" style="display:none">
        <div class="card p-6">
          <h3 class="font-700 text-white mb-2">Nouvelle Demande d'Absence</h3>
          <p class="text-xs text-slate-400 mb-5">Pour une absence passee (avec justificatif) ou une absence future prevue</p>
          <form id="absence-form" class="space-y-4" onsubmit="soumettreAbsence(event)">
            <div>
              <label class="text-xs text-slate-500 block mb-1">Type de demande *</label>
              <select id="abs-type-demande" onchange="changerTypeAbsence(this.value)" required>
                <option value="">Choisir...</option>
                <option value="absence_passee">Absence passee — je justifie une absence</option>
                <option value="absence_future">Absence future — je previens a l'avance</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-slate-500 block mb-1" id="abs-date-label">Date de l'absence *</label>
              <input type="date" id="abs-date" required>
              <p id="abs-date-hint" class="text-xs text-slate-500 mt-1"></p>
            </div>
            <div>
              <label class="text-xs text-slate-500 block mb-1">Motif *</label>
              <select id="abs-motif" required>
                <option value="">Choisir un motif...</option>
                <option value="maladie">Maladie</option>
                <option value="urgence">Urgence familiale</option>
                <option value="rendez_vous_medical">Rendez-vous medical</option>
                <option value="probleme_transport">Probleme de transport</option>
                <option value="autre">Autre</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-slate-500 block mb-1">Details (optionnel)</label>
              <textarea id="abs-details" rows="3" placeholder="Expliquez les circonstances..."></textarea>
            </div>
            <div id="abs-document-section" style="display:none">
              <label class="text-xs text-slate-500 block mb-1">Document justificatif (PDF, JPG, PNG — max 5MB)</label>
              <input type="file" id="abs-document" accept=".pdf,.jpg,.jpeg,.png" style="padding:8px;cursor:pointer">
              <p class="text-xs text-slate-500 mt-1">Certificat medical, attestation... (optionnel mais recommande)</p>
            </div>
            <div id="abs-info-box" style="background:#1a2333;border:1px solid #1e3a5f;border-radius:10px;padding:12px 16px;font-size:13px;color:#60a5fa;display:none"></div>
            <div class="flex gap-3">
              <button type="submit" id="abs-submit-btn" class="btn-primary flex-1 py-3" disabled style="opacity:0.5;cursor:not-allowed">Soumettre la demande</button>
              <button type="button" onclick="fermerFormulaireAbsence()" class="btn-secondary py-3 px-6">Annuler</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function updateClock(){document.getElementById('clock-display').textContent=new Date().toLocaleTimeString('fr-FR');}
setInterval(updateClock,1000);updateClock();

function toggleSidebar(){
  var sidebar=document.getElementById('sidebar');
  var overlay=document.getElementById('sidebar-overlay');
  var isMobile=window.innerWidth<=768;
  if(isMobile){sidebar.classList.toggle('mobile-open');overlay.classList.toggle('active');}
  else{sidebar.classList.toggle('collapsed');document.getElementById('main-content').classList.toggle('expanded');}
}
function closeSidebarMobile(){
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebar-overlay').classList.remove('active');
}

function switchView(name){
  document.querySelectorAll('.view').forEach(function(v){v.classList.remove('active');});
  document.querySelectorAll('.nav-item').forEach(function(n){n.classList.remove('active');});
  var v=document.getElementById('view-'+name);if(v)v.classList.add('active');
  var n=document.querySelector('[data-view="'+name+'"]');if(n)n.classList.add('active');
  var titles={accueil:'Mon Tableau de bord',pointer:'Me Pointer',monqr:'Mon QR Code',historique:'Mes Pointages',absences:'Mes Absences'};
  document.getElementById('page-title').textContent=titles[name]||'';
  if(name==='pointer') initPointer();
}
document.getElementById('main-nav').addEventListener('click',function(e){
  var i=e.target.closest('[data-view]');
  if(i){switchView(i.dataset.view);if(window.innerWidth<=768)closeSidebarMobile();}
});

<?php if ($qrToken): ?>
document.addEventListener('DOMContentLoaded',function(){
  var grand=document.getElementById('qr-grand');
  if(grand){new QRCode(grand,{text:'<?= $qrToken ?>',width:220,height:220,colorDark:'#000',colorLight:'#fff',correctLevel:QRCode.CorrectLevel.H});}
});
<?php endif; ?>

function telechargerQR(){
  var c=document.getElementById('qr-grand');if(!c){showToast('QR non trouve','error');return;}
  var canvas=c.querySelector('canvas');var img=c.querySelector('img');
  if(canvas){var a=document.createElement('a');a.download='qrcode-<?= htmlspecialchars($nom) ?>.png';a.href=canvas.toDataURL('image/png');a.click();showToast('Telecharge !','success');}
  else if(img){var a=document.createElement('a');a.download='qrcode-<?= htmlspecialchars($nom) ?>.png';a.href=img.src;a.click();showToast('Telecharge !','success');}
  else{showToast('QR non genere','error');}
}
function regenererMonQR(){
  if(!confirm('Regenerer votre QR Code ? L\'ancien ne fonctionnera plus du tout.'))return;
  fetch('index.php?route=etudiant/qr/regenerer',{method:'POST'})
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){showToast('QR Code regenere !','success');setTimeout(function(){location.reload();},1200);}
    else{showToast(res.message||'Erreur','error');}
  }).catch(function(){showToast('Erreur reseau','error');});
}

function ouvrirFormulaireAbsence(){var f=document.getElementById('form-absence');if(f){f.style.display='block';f.scrollIntoView({behavior:'smooth'});}}
function fermerFormulaireAbsence(){var f=document.getElementById('form-absence');if(f)f.style.display='none';}

function changerTypeAbsence(type){
  var dateInput=document.getElementById('abs-date');
  var dateLabel=document.getElementById('abs-date-label');
  var dateHint=document.getElementById('abs-date-hint');
  var docSection=document.getElementById('abs-document-section');
  var infoBox=document.getElementById('abs-info-box');
  var submitBtn=document.getElementById('abs-submit-btn');
  var today=new Date().toISOString().split('T')[0];
  if(type==='absence_passee'){
    dateLabel.textContent='Date de l\'absence *';dateHint.textContent='Choisissez la date ou vous etiez absent';
    dateInput.max=today;dateInput.min='';dateInput.value='';
    docSection.style.display='block';infoBox.style.display='block';infoBox.style.color='#60a5fa';
    infoBox.textContent='Vous justifiez une absence passee. Joignez un document si possible.';
  } else if(type==='absence_future'){
    dateLabel.textContent='Date de l\'absence prevue *';dateHint.textContent='Choisissez la date a venir';
    var tomorrow=new Date();tomorrow.setDate(tomorrow.getDate()+1);
    dateInput.min=tomorrow.toISOString().split('T')[0];dateInput.max='';dateInput.value='';
    docSection.style.display='none';infoBox.style.display='block';infoBox.style.color='#fbbf24';
    infoBox.textContent='Vous signalez une absence future. L\'administrateur devra valider.';
  }
  submitBtn.disabled=false;submitBtn.style.opacity='1';submitBtn.style.cursor='pointer';
}

function soumettreAbsence(e){
  e.preventDefault();
  var typeDemande=document.getElementById('abs-type-demande').value;
  var date=document.getElementById('abs-date').value;
  var motif=document.getElementById('abs-motif').value;
  var details=document.getElementById('abs-details').value;
  var docFile=document.getElementById('abs-document').files[0];
  if(!typeDemande||!date||!motif){showToast('Veuillez remplir tous les champs requis','error');return;}
  var formData=new FormData();
  formData.append('type',motif);formData.append('start_date',date);formData.append('end_date',date);
  formData.append('date_absence',date);formData.append('reason',details);
  if(docFile)formData.append('document',docFile);
  var btn=document.getElementById('abs-submit-btn');btn.disabled=true;btn.textContent='Envoi en cours...';
  fetch('index.php?route=conges/soumettre',{method:'POST',body:formData})
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){showToast('Demande envoyee !','success');fermerFormulaireAbsence();setTimeout(function(){location.reload();},1500);}
    else{showToast(res.message||'Erreur','error');btn.disabled=false;btn.textContent='Soumettre la demande';}
  }).catch(function(){showToast('Erreur reseau','error');btn.disabled=false;btn.textContent='Soumettre la demande';});
}

// ============================================
// SCANNER + GEOLOCALISATION
// ============================================
var ECOLE_LAT=14.679620,ECOLE_LNG=-17.441229,RAYON_MAX=500;
var positionOK=false;
var scannerEcole=null,scanEcoleActif=false,lastScanEcole=0;
var scannerPerso=null,scanPersoActif=false,lastScanPerso=0;

function calculerDistance(lat1,lng1,lat2,lng2){
  var R=6371000;var dLat=(lat2-lat1)*Math.PI/180;var dLng=(lng2-lng1)*Math.PI/180;
  var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}
function initPointer(){
  positionOK=false;
  var statusBox=document.getElementById('geo-status');
  var statusText=document.getElementById('geo-status-text');
  var distText=document.getElementById('geo-distance-text');
  statusBox.style.borderLeftColor='#60a5fa';
  statusText.textContent='Chargement de la configuration...';
  distText.textContent='En attente';
  fetch('index.php?route=school/config')
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success&&res.data){
      ECOLE_LAT=parseFloat(res.data.latitude)||14.679620;
      ECOLE_LNG=parseFloat(res.data.longitude)||-17.441229;
      RAYON_MAX=parseInt(res.data.rayon)||500;
      var el=document.getElementById('rayon-display');if(el)el.textContent=RAYON_MAX+' metres';
    }
    _demarrerGPS(statusBox,statusText,distText);
  }).catch(function(){_demarrerGPS(statusBox,statusText,distText);});
}
function _demarrerGPS(statusBox,statusText,distText){
  statusText.textContent='Verification de votre position...';distText.textContent='En attente GPS';
  if(!navigator.geolocation){
    statusBox.style.borderLeftColor='#f87171';statusText.textContent='Geolocalisation non supportee';distText.textContent='Votre navigateur ne supporte pas le GPS';return;
  }
  navigator.geolocation.getCurrentPosition(
    function(pos){
      var dist=Math.round(calculerDistance(pos.coords.latitude,pos.coords.longitude,ECOLE_LAT,ECOLE_LNG));
      if(dist<=RAYON_MAX){
        positionOK=true;statusBox.style.borderLeftColor='#34d399';statusText.textContent='Position validee — vous etes sur place';distText.textContent='Distance : '+dist+'m de l\'etablissement';
        setStatus('scan-status-ecole','Position OK — Activez la camera','success');setStatus('scan-status-perso','Position OK — Activez la camera','success');
      } else {
        positionOK=false;statusBox.style.borderLeftColor='#f87171';statusText.textContent='Hors du perimetre autorise';distText.textContent='Distance : '+dist+'m (max '+RAYON_MAX+'m) — Rapprochez-vous';
        setStatus('scan-status-ecole','Hors perimetre — pointage impossible ('+dist+'m)','error');setStatus('scan-status-perso','Hors perimetre — pointage impossible ('+dist+'m)','error');
      }
    },
    function(err){
      statusBox.style.borderLeftColor='#fbbf24';statusText.textContent='Position indisponible';
      distText.textContent=err.code===1?'Autorisez la localisation dans votre navigateur':'Erreur GPS';
      setStatus('scan-status-ecole','GPS indisponible — autorisez la localisation','warning');
    },
    {enableHighAccuracy:true,timeout:10000,maximumAge:0}
  );
}
function switchTab(tab){
  if(scanEcoleActif)arreterScanEcole();if(scanPersoActif)arreterScanPerso();
  document.getElementById('tab-ecole').style.display=tab==='ecole'?'block':'none';
  document.getElementById('tab-perso').style.display=tab==='perso'?'block':'none';
  var base='padding:8px 16px;font-size:14px;background:none;cursor:pointer;border-top:none;border-left:none;border-right:none;font-family:inherit;';
  document.getElementById('tab-btn-ecole').style.cssText=base+(tab==='ecole'?'font-weight:600;border-bottom:2px solid #34d399;color:#34d399':'font-weight:500;border-bottom:2px solid transparent;color:#94a3b8');
  document.getElementById('tab-btn-perso').style.cssText=base+(tab==='perso'?'font-weight:600;border-bottom:2px solid #34d399;color:#34d399':'font-weight:500;border-bottom:2px solid transparent;color:#94a3b8');
}
function setStatus(elId,msg,type){
  var el=document.getElementById(elId);if(!el)return;el.textContent=msg;
  var s={info:'background:#1a2333;color:#60a5fa;border:1px solid #1e3a5f',success:'background:#0d2a1e;color:#34d399;border:1px solid #064e3b',error:'background:#2d1414;color:#f87171;border:1px solid #7f1d1d',warning:'background:#2d2006;color:#fbbf24;border:1px solid #78350f'};
  el.style.cssText='padding:12px;border-radius:10px;text-align:center;font-size:14px;font-weight:500;margin-bottom:12px;'+(s[type]||s.info);
}
function demarrerScanEcole(){
  if(!positionOK){showToast('Hors perimetre — pointage refuse','error');return;}
  if(scanEcoleActif)return;
  scannerEcole=new Html5Qrcode('etu-qr-reader-ecole');
  scannerEcole.start({facingMode:'environment'},{fps:10,qrbox:{width:220,height:220}},
    function(decoded){var now=Date.now();if(now-lastScanEcole<3000)return;lastScanEcole=now;setStatus('scan-status-ecole','QR detecte ! Traitement...','info');envoyerViaQrEcole(decoded);},
    function(){}
  ).then(function(){scanEcoleActif=true;document.getElementById('btn-start-ecole').style.display='none';document.getElementById('btn-stop-ecole').style.display='block';setStatus('scan-status-ecole','Camera active — Scannez le QR de l\'ecole','success');})
  .catch(function(){setStatus('scan-status-ecole','Impossible d\'acceder a la camera','error');});
}
function arreterScanEcole(){
  if(!scannerEcole||!scanEcoleActif)return;
  scannerEcole.stop().then(function(){scanEcoleActif=false;document.getElementById('btn-start-ecole').style.display='block';document.getElementById('btn-stop-ecole').style.display='none';document.getElementById('etu-qr-reader-ecole').innerHTML='';setStatus('scan-status-ecole','Camera arretee','info');});
}
function envoyerViaQrEcole(tokenEcole){
  fetch('index.php?route=school/pointer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'token_ecole='+encodeURIComponent(tokenEcole)})
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){
      var libelle=res.data&&res.data.mode==='depart'?'Depart':'Arrivee';
      setStatus('scan-status-ecole',libelle+' enregistree !','success');showToast(res.message||'Pointage enregistre !','success');arreterScanEcole();setTimeout(function(){location.reload();},2000);
    } else {
      setStatus('scan-status-ecole','Erreur : '+res.message,'error');showToast(res.message,'error');
    }
  }).catch(function(){showToast('Erreur reseau','error');});
}
function demarrerScanPerso(){
  if(!positionOK){showToast('Hors perimetre — pointage refuse','error');return;}
  if(scanPersoActif)return;
  scannerPerso=new Html5Qrcode('etu-qr-reader-perso');
  scannerPerso.start({facingMode:'environment'},{fps:10,qrbox:{width:220,height:220}},
    function(decoded){var now=Date.now();if(now-lastScanPerso<3000)return;lastScanPerso=now;setStatus('scan-status-perso','QR detecte ! Traitement...','info');envoyerViaQrPerso(decoded);},
    function(){}
  ).then(function(){scanPersoActif=true;document.getElementById('btn-start-perso').style.display='none';document.getElementById('btn-stop-perso').style.display='block';setStatus('scan-status-perso','Camera active — Scannez votre QR Code','success');})
  .catch(function(){setStatus('scan-status-perso','Impossible d\'acceder a la camera','error');});
}
function arreterScanPerso(){
  if(!scannerPerso||!scanPersoActif)return;
  scannerPerso.stop().then(function(){scanPersoActif=false;document.getElementById('btn-start-perso').style.display='block';document.getElementById('btn-stop-perso').style.display='none';document.getElementById('etu-qr-reader-perso').innerHTML='';setStatus('scan-status-perso','Camera arretee','info');});
}
function envoyerViaQrPerso(token){
  fetch('index.php?route=presences/pointer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'token='+encodeURIComponent(token)})
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){
      var libelle=res.data&&res.data.mode==='depart'?'Depart':'Arrivee';
      setStatus('scan-status-perso',libelle+' enregistree !','success');showToast(res.message||'Pointage enregistre !','success');arreterScanPerso();setTimeout(function(){location.reload();},2000);
    } else {
      setStatus('scan-status-perso','Erreur : '+res.message,'error');showToast(res.message,'error');
    }
  }).catch(function(){showToast('Erreur reseau','error');});
}

var toastTimeout;
function showToast(msg,type){
  type=type||'info';var ex=document.querySelector('.toast');if(ex)ex.remove();clearTimeout(toastTimeout);
  var colors={success:'#34d399',error:'#f87171',info:'#60a5fa'};
  var t=document.createElement('div');t.className='toast';
  t.innerHTML='<span style="color:'+colors[type]+';font-size:16px">'+(type==='success'?'&#10003;':'&#9432;')+'</span><span style="color:#e2e8f0">'+msg+'</span>';
  document.body.appendChild(t);toastTimeout=setTimeout(function(){t.remove();},3500);
}
</script>
</body>
</html>