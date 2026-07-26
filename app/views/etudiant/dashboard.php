<?php
$nom             = $_SESSION['user']['nom']   ?? 'Etudiant';
$email           = $_SESSION['user']['email'] ?? '';
$role            = $_SESSION['user']['role']  ?? 'etudiant';
$dept            = $_SESSION['user']['department'] ?? '';
$photo           = $_SESSION['user']['photo'] ?? null;
$qrToken         = $qrToken         ?? null;
$pointageAujourdhui = $pointageAujourdhui ?? null;
$presents        = $presents        ?? 0;
$retards         = $retards         ?? 0;
$absences        = $absences        ?? 0;
$totalJours      = $totalJours      ?? 0;
$historique      = $historique      ?? [];
$conges          = $conges          ?? [];

$scanMsg  = $_GET['scan_msg']  ?? '';
$scanType = $_GET['scan_type'] ?? '';

// Photo de profil
$photoUrl = $photo ? 'uploads/photos/' . htmlspecialchars($photo) : null;
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
    input[type="file"]{padding:8px;cursor:pointer}
    input[type="date"]{color-scheme:dark;cursor:pointer}
    input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1);opacity:.6;cursor:pointer}
    .btn-primary{background:#059669;color:white;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-primary:hover{background:#047857;transform:translateY(-1px)}
    .btn-secondary{background:#1e2a4a;color:#e2e8f0;border:1px solid #2d3f6a;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-secondary:hover{background:#253354}
    .btn-danger{background:#7f1d1d;color:#fca5a5;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-danger:hover{background:#991b1b}
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
    /* Photo de profil */
    .avatar-upload{position:relative;display:inline-block;cursor:pointer}
    .avatar-upload input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;border-radius:50%;width:100%;height:100%}
    .avatar-img{width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #1e2a4a}
    .avatar-initials{width:96px;height:96px;border-radius:50%;background:#064e3b;color:#34d399;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:28px;border:3px solid #1e2a4a}
    .avatar-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;font-size:20px}
    .avatar-upload:hover .avatar-overlay{opacity:1}
    /* Strength password */
    .pwd-bar{height:4px;border-radius:2px;background:#1e2a4a;overflow:hidden;margin-top:6px}
    .pwd-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0}
    @media (max-width: 768px) {
      .sidebar{transform:translateX(-240px)}
      .sidebar.mobile-open{transform:translateX(0);box-shadow:4px 0 20px rgba(0,0,0,.5)}
      .main-content{margin-left:0 !important}
      .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}
      .sidebar-overlay.active{display:block}
      #page-title{font-size:14px}
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
      .grid.grid-cols-1.sm\:grid-cols-2.xl\:grid-cols-3{grid-template-columns:1fr}
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
  <div class="p-3 mt-2 flex-1 overflow-y-auto">
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
        Mes Demandes
      </div>
      <div class="nav-item" data-view="profil">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mon Profil
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
      <?php if ($photoUrl): ?>
        <img src="<?= $photoUrl ?>" alt="photo" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0">
      <?php else: ?>
        <div style="width:36px;height:36px;border-radius:50%;background:#064e3b;color:#34d399;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0"><?= strtoupper(substr($nom, 0, 2)) ?></div>
      <?php endif; ?>
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
      <span class="text-xs bg-emerald-900/50 text-emerald-400 border border-emerald-800 px-3 py-1 rounded-full hidden sm:inline"><?= htmlspecialchars($dept) ?></span>
    </div>
  </div>

  <div class="p-6">

    <?php if (!empty($scanMsg)): ?>
    <?php
      $scanStyles = [
        'scan_ok'   => ['border'=>'#34d399','bg'=>'#0a1f15','icon'=>'#34d399','emoji'=>'✅','iconBg'=>'#064e3b'],
        'scan_info' => ['border'=>'#60a5fa','bg'=>'#0a1020','icon'=>'#60a5fa','emoji'=>'ℹ️','iconBg'=>'#1e3a5f'],
        'erreur'    => ['border'=>'#f87171','bg'=>'#1a0808','icon'=>'#f87171','emoji'=>'⚠️','iconBg'=>'#7f1d1d'],
      ];
      $s = $scanStyles[$scanType] ?? $scanStyles['scan_info'];
    ?>
    <div class="scan-confirm mb-5 p-4 rounded-2xl flex items-center gap-4" style="background:<?= $s['bg'] ?>;border:1px solid <?= $s['border'] ?>">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 text-xl" style="background:<?= $s['iconBg'] ?>"><?= $s['emoji'] ?></div>
      <div>
        <p class="font-600 text-white text-sm"><?= htmlspecialchars($scanMsg) ?></p>
        <p class="text-xs mt-0.5" style="color:<?= $s['icon'] ?>">
          <?php if ($scanType === 'scan_ok'): ?>Pointage enregistre via QR Code
          <?php elseif ($scanType === 'erreur'): ?>Contactez l'administrateur si le probleme persiste
          <?php else: ?>Informations de pointage<?php endif; ?>
        </p>
      </div>
      <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;color:#475569;cursor:pointer;font-size:18px;padding:4px 8px">×</button>
    </div>
    <?php endif; ?>

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
        <div class="card p-5 stat-accent-green"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Presents</p><p class="text-3xl font-700 text-white mono"><?= $presents ?></p><p class="text-xs text-emerald-400 mt-1">Ce mois</p></div>
        <div class="card p-5 stat-accent-yellow"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Retards</p><p class="text-3xl font-700 text-white mono"><?= $retards ?></p><p class="text-xs text-yellow-400 mt-1">Ce mois</p></div>
        <div class="card p-5 stat-accent-red"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Absences</p><p class="text-3xl font-700 text-white mono"><?= $absences ?></p><p class="text-xs text-red-400 mt-1">Ce mois</p></div>
        <div class="card p-5 stat-accent-blue"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Total Jours</p><p class="text-3xl font-700 text-white mono"><?= $totalJours ?></p><p class="text-xs text-blue-400 mt-1">Ce mois</p></div>
      </div>
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
            <?php $badges=['present'=>'badge-present','retard'=>'badge-retard','absence'=>'badge-absent'];$labels=['present'=>'Present','retard'=>'Retard','absence'=>'Absent'];$cls=$badges[$h['type']]??'badge-pending';$lbl=$labels[$h['type']]??$h['type']; ?>
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
          <p class="text-xs text-slate-400 mb-4">Scannez le QR Code affiche a l'entree — arrivee ou depart detecte automatiquement</p>
          <div id="scan-status-ecole" class="mb-3 p-3 rounded-xl text-center text-sm font-500" style="background:#1a2333;color:#60a5fa;border:1px solid #1e3a5f">Camera non active</div>
          <div id="etu-qr-reader-ecole" class="mb-3 rounded-xl overflow-hidden" style="background:#000;min-height:60px"></div>
          <div class="grid grid-cols-2 gap-2">
            <button id="btn-start-ecole" onclick="demarrerScanEcole()" class="btn-primary py-2.5 text-sm">Activer Camera</button>
            <button id="btn-stop-ecole" onclick="arreterScanEcole()" class="btn-secondary py-2.5 text-sm" style="display:none">Arreter Camera</button>
          </div>
        </div>
        <div id="tab-perso" class="card p-5" style="display:none">
          <p class="font-600 text-white mb-1">Scanner mon QR Code personnel</p>
          <p class="text-xs text-slate-400 mb-4">Scannez votre propre QR Code — arrivee ou depart detecte automatiquement</p>
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
              <button onclick="regenererMonQR()" class="btn-danger flex-1 py-3">Regenerer</button>
            </div>
          <?php else: ?>
            <p class="text-slate-500">Pas de QR Code disponible. Contactez l'administrateur.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  <div id="view-historique" class="view animate-in">
      <div class="card">
        <div class="p-5 border-b border-[#1e2a4a]">
          <p class="font-600 text-white">Historique complet de mes pointages</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
          <?php 
          // On commence par filtrer l'historique pour exclure les absences déjà approuvées
          $historiqueAffiche = array_filter($historique, function($h) use ($conges) {
              if ($h['type'] === 'absence') {
                  foreach ($conges as $c) {
                      // On vérifie si une justification existe à cette date et si elle est approuvée
                      // (Adapte 'start_date' ou le champ correspondant selon ta structure)
                      if ($c['start_date'] === $h['date'] && $c['status'] === 'approuve') {
                          return false; // On ne garde pas ce pointage dans l'affichage
                      }
                  }
              }
              return true; // On garde tous les autres pointages
          });
          ?>

          <?php if (empty($historiqueAffiche)): ?>
            <p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucun pointage enregistré</p>
          <?php else: ?>
            <?php foreach ($historiqueAffiche as $h): ?>
            <?php
              $duree='--';
              if($h['check_in']&&$h['check_out']){$ci=explode(':',$h['check_in']);$co=explode(':',$h['check_out']);$mins=(intval($co[0])*60+intval($co[1]))-(intval($ci[0])*60+intval($ci[1]));$duree=floor($mins/60).'h'.str_pad($mins%60,2,'0',STR_PAD_LEFT);}
              $badges=['present'=>'badge-present','retard'=>'badge-retard','absence'=>'badge-absent'];
              $labels=['present'=>'Présent','retard'=>'Retard','absence'=>'Absent'];
              $cls=$badges[$h['type']]??'badge-pending';$lbl=$labels[$h['type']]??$h['type'];
              
              // Vérifier si une justification est déjà en attente pour cette date
              $dejaDemande = false;
              foreach ($conges as $c) {
                  if ($c['start_date'] === $h['date']) {
                      $dejaDemande = true;
                      break;
                  }
              }
            ?>
            <div class="card p-4 flex flex-col gap-3 justify-between">
              <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                  <span class="mono text-sm text-white"><?= $h['date'] ?></span>
                  <span class="text-xs <?= $cls ?> px-2.5 py-1 rounded-full"><?= $lbl ?></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-500">Durée</span>
                  <span class="mono text-slate-400"><?= $duree ?></span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-[#1e2a4a]">
                  <div class="text-xs"><span class="text-slate-600">Arrivée</span><br><span class="mono text-emerald-400"><?= $h['check_in'] ? substr($h['check_in'],0,5) : '--' ?></span></div>
                  <div class="text-xs text-right"><span class="text-slate-600">Départ</span><br><span class="mono text-blue-400"><?= $h['check_out'] ? substr($h['check_out'],0,5) : '--' ?></span></div>
                </div>
              </div>
              
              <?php if($h['type'] === 'absence'): ?>
                <div class="pt-2">
                  <?php if($dejaDemande): ?>
                    <div class="w-full text-center text-xs bg-amber-900/20 text-amber-400 border border-amber-800/40 py-2 rounded-xl font-medium cursor-not-allowed">
                      Justification en attente...
                    </div>
                  <?php else: ?>
                    <button onclick="ouvrirModalJustifier('<?= $h['date'] ?>')" class="w-full text-center text-xs bg-red-900/40 hover:bg-red-900/60 text-red-300 border border-red-800/60 py-2 rounded-xl transition-all font-medium">
                      Justifier mon absence
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- MODALE DE JUSTIFICATION DE POINTAGE -->
    <div id="modal-justifier" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
      <!-- Overlay sombre -->
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="fermerModalJustifier()"></div>
      <!-- Contenu modale -->
      <div class="card w-full max-w-md p-6 relative z-10 animate-in">
        <h3 class="font-700 text-white text-lg mb-1">Justifier l'absence</h3>
        <p class="text-xs text-slate-400 mb-4">Date ciblée : <span id="modal-date-display" class="mono text-emerald-400 font-600"></span></p>
        
        <form id="modal-justif-form" class="space-y-4" onsubmit="soumettreJustificationModal(event)">
          <input type="hidden" id="modal-abs-date">
          
          <div>
            <label class="text-xs text-slate-500 block mb-1">Motif *</label>
            <select id="modal-abs-motif" required>
              <option value="">Choisir un motif...</option>
              <option value="maladie">Maladie</option>
              <option value="urgence">Urgence familiale</option>
              <option value="rendez_vous_medical">Rendez-vous médical</option>
              <option value="probleme_transport">Problème de transport</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          
          <div>
            <label class="text-xs text-slate-500 block mb-1">Détails / Explications</label>
            <textarea id="modal-abs-details" rows="3" placeholder="Précisez les raisons de votre absence..."></textarea>
          </div>
          
          <div>
            <label class="text-xs text-slate-500 block mb-1">Fichier justificatif (PDF, JPG, PNG — max 5MB)</label>
            <input type="file" id="modal-abs-document" accept=".pdf,.jpg,.jpeg,.png">
            <p class="text-[11px] text-slate-500 mt-1">Certificat médical, attestation...</p>
          </div>
          
          <div class="flex gap-3 pt-2">
            <button type="submit" id="modal-abs-submit-btn" class="btn-primary flex-1 py-2.5 text-sm">Envoyer à l'admin</button>
            <button type="button" onclick="fermerModalJustifier()" class="btn-secondary py-2.5 px-4 text-sm">Annuler</button>
          </div>
        </form>
      </div>
    </div>

    <div id="view-absences" class="view animate-in">
      <div class="flex justify-end mb-4">
        <button onclick="ouvrirFormulaireAbsence()" class="btn-primary text-sm px-4 py-2">+ Nouvelle demande</button>
      </div>
      
      <div class="card mb-5">
        <div class="p-5 border-b border-[#1e2a4a]">
          <p class="font-600 text-white">Mes Demandes d'Absence Future</p>
          <p class="text-xs text-slate-400 mt-1">Planifier ou déclarer une absence à l'avance</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
          <?php 
          // On filtre pour n'afficher ici que les demandes dont la date est supérieure ou égale à aujourd'hui
          $today = date('Y-m-d');
          $demandesFutures = array_filter($conges, function($c) use ($today) {
              return $c['start_date'] >= $today;
          });
          ?>

          <?php if (empty($demandesFutures)): ?>
            <p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucune demande d'absence future enregistrée</p>
          <?php else: ?>
            <?php foreach ($demandesFutures as $c): ?>
            <?php
              $badgesC = ['en_attente'=>'badge-pending', 'approuve'=>'badge-present', 'refuse'=>'badge-absent'];
              $labelsC = ['en_attente'=>'En attente', 'approuve'=>'Approuvé', 'refuse'=>'Refusé'];
              $clsC = $badgesC[$c['status']] ?? 'badge-pending';
              $lblC = $labelsC[$c['status']] ?? $c['status'];
              
              $typeLabel = [
                'maladie'=>'Maladie',
                'conge_annuel'=>'Congé',
                'urgence'=>'Urgence familiale',
                'rendez_vous_medical'=>'Rendez-vous médical',
                'probleme_transport'=>'Problème de transport',
                'autre'=>'Autre'
              ];
            ?>
            <div class="card p-4 flex flex-col gap-3">
              <div class="flex items-center justify-between gap-2">
                <div>
                  <span class="mono text-sm text-white"><?= $c['start_date'] ?></span>
                  <span class="text-[10px] badge-pending px-1.5 py-0.5 rounded ml-1">Future</span>
                </div>
                <span class="text-xs <?= $clsC ?> px-2.5 py-1 rounded-full"><?= $lblC ?></span>
              </div>
              <div class="text-xs text-slate-400 font-medium"><?= $typeLabel[$c['type']] ?? $c['type'] ?></div>
              <?php if (!empty($c['reason'])): ?>
                <p class="text-xs text-slate-400 line-clamp-2 bg-[#0e1726] p-2 rounded border border-[#1e2a4a]/50"><?= htmlspecialchars($c['reason']) ?></p>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div id="form-absence" style="display:none">
        <div class="card p-6">
          <h3 class="font-700 text-white mb-2">Nouvelle Demande d'Absence Future</h3>
          <p class="text-xs text-slate-400 mb-5">Prévoyez et avertissez l'administration de votre indisponibilité à venir.</p>
          
          <form id="absence-form" class="space-y-4" onsubmit="soumettreAbsence(event)">
            <div>
              <label class="text-xs text-slate-500 block mb-1">Motif de l'absence *</label>
              <select id="abs-motif" name="type" required>
                <option value="">Choisir un motif...</option>
                <option value="maladie">Maladie / Soins prévus</option>
                <option value="urgence">Urgence familiale</option>
                <option value="rendez_vous_medical">Rendez-vous médical</option>
                <option value="probleme_transport">Problème de transport anticipé</option>
                <option value="autre">Autre raison</option>
              </select>
            </div>

            <div>
              <label class="text-xs text-slate-500 block mb-1">Date de l'absence prévue *</label>
              <input type="date" id="abs-date" name="start_date" min="<?= date('Y-m-d') ?>" required>
              <p class="text-[11px] text-slate-500 mt-1">Seules les dates d'aujourd'hui et futures sont sélectionnables.</p>
            </div>

            <div>
              <label class="text-xs text-slate-500 block mb-1">Détails / Justifications de la demande (optionnel)</label>
              <textarea id="abs-details" name="reason" rows="3" placeholder="Précisez les circonstances de votre future absence..."></textarea>
            </div>

            <div class="flex gap-3 pt-2">
              <button type="submit" id="abs-submit-btn" class="btn-primary flex-1 py-3">Envoyer la demande</button>
              <button type="button" onclick="fermerFormulaireAbsence()" class="btn-secondary py-3 px-6">Annuler</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- ============================================ -->
    <!-- MON PROFIL                                   -->
    <!-- ============================================ -->
    <div id="view-profil" class="view animate-in">
      <div class="max-w-lg mx-auto space-y-5">

        <!-- CARTE PHOTO + INFOS -->
        <div class="card p-6">
          <h2 class="font-700 text-white text-lg mb-5">Mon Profil</h2>

          <!-- Photo de profil -->
          <div class="flex flex-col items-center gap-4 mb-6 pb-6 border-b border-[#1e2a4a]">
            <div class="avatar-upload" title="Cliquer pour changer la photo">
              <?php if ($photoUrl): ?>
                <img src="<?= $photoUrl ?>" alt="photo" class="avatar-img" id="preview-photo">
              <?php else: ?>
                <div class="avatar-initials" id="preview-initials"><?= strtoupper(substr($nom, 0, 2)) ?></div>
                <img src="" alt="" class="avatar-img" id="preview-photo" style="display:none">
              <?php endif; ?>
              <div class="avatar-overlay">📷</div>
              <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
            </div>
            <div class="text-center">
              <p class="text-white font-600"><?= htmlspecialchars($nom) ?></p>
              <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($email) ?></p>
              <span class="text-xs badge-present px-2 py-0.5 rounded-full mt-1 inline-block"><?= htmlspecialchars($dept) ?></span>
            </div>
            <button onclick="uploadPhoto()" id="btn-upload-photo" class="btn-secondary text-sm px-4 py-2" style="display:none">
              Enregistrer la photo
            </button>
          </div>

          <!-- Modifier nom -->
          <div class="space-y-4">
            <div>
              <label class="text-xs text-slate-500 block mb-1">Nom complet</label>
              <input type="text" id="profil-nom" value="<?= htmlspecialchars($nom) ?>" placeholder="Votre nom complet">
            </div>
            <button onclick="sauvegarderNom()" class="btn-primary w-full py-2.5 text-sm">
              Mettre a jour le nom
            </button>
          </div>
        </div>

        <!-- CHANGER MOT DE PASSE -->
        <div class="card p-6">
          <h3 class="font-600 text-white mb-1">Changer le mot de passe</h3>
          <p class="text-xs text-slate-400 mb-5">Laissez vide si vous ne souhaitez pas le changer</p>
          <div class="space-y-4">
            <div>
              <label class="text-xs text-slate-500 block mb-1">Mot de passe actuel</label>
              <div style="position:relative">
                <input type="password" id="pwd-actuel" placeholder="Votre mot de passe actuel">
                <button type="button" onclick="togglePwd('pwd-actuel',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#64748b;cursor:pointer;font-size:16px">👁</button>
              </div>
            </div>
            <div>
              <label class="text-xs text-slate-500 block mb-1">Nouveau mot de passe</label>
              <div style="position:relative">
                <input type="password" id="pwd-nouveau" placeholder="Au moins 6 caracteres" oninput="evaluerForce(this.value)">
                <button type="button" onclick="togglePwd('pwd-nouveau',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#64748b;cursor:pointer;font-size:16px">👁</button>
              </div>
              <!-- Barre de force -->
              <div class="pwd-bar mt-2"><div class="pwd-fill" id="pwd-fill"></div></div>
              <p class="text-xs mt-1" id="pwd-force-label" style="color:#64748b"></p>
            </div>
            <div>
              <label class="text-xs text-slate-500 block mb-1">Confirmer le nouveau mot de passe</label>
              <div style="position:relative">
                <input type="password" id="pwd-confirm" placeholder="...">
                <button type="button" onclick="togglePwd('pwd-confirm',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#64748b;cursor:pointer;font-size:16px">👁</button>
              </div>
            </div>
            <button onclick="changerMotDePasse()" class="btn-primary w-full py-2.5 text-sm">
              Changer le mot de passe
            </button>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
function updateClock(){document.getElementById('clock-display').textContent=new Date().toLocaleTimeString('fr-FR');}
setInterval(updateClock,1000);updateClock();

function toggleSidebar(){
  var sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebar-overlay'),isMobile=window.innerWidth<=768;
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
  var titles={accueil:'Mon Tableau de bord',pointer:'Me Pointer',monqr:'Mon QR Code',historique:'Mes Pointages',absences:'Mes Absences',profil:'Mon Profil'};
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
  var canvas=c.querySelector('canvas');
  if(canvas){var a=document.createElement('a');a.download='qrcode-<?= htmlspecialchars($nom) ?>.png';a.href=canvas.toDataURL('image/png');a.click();showToast('Telecharge !','success');}
  else{showToast('QR non genere','error');}
}
function regenererMonQR(){
  if(!confirm('Regenerer votre QR Code ? L\'ancien ne fonctionnera plus.'))return;
  fetch('index.php?route=etudiant/qr/regenerer',{method:'POST'})
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){showToast('QR regenere !','success');setTimeout(function(){location.reload();},1200);}
    else{showToast(res.message||'Erreur','error');}
  }).catch(function(){showToast('Erreur reseau','error');});
}

// ============================================
// PROFIL — PHOTO
// ============================================
function previewPhoto(input){
  if(!input.files||!input.files[0])return;
  var file=input.files[0];
  if(file.size > 5*1024*1024){showToast('Image trop lourde (max 5MB)','error');input.value='';return;}
  var reader=new FileReader();
  reader.onload=function(e){
    var img=document.getElementById('preview-photo');
    var ini=document.getElementById('preview-initials');
    img.src=e.target.result;img.style.display='block';
    if(ini)ini.style.display='none';
  };
  reader.readAsDataURL(file);
  document.getElementById('btn-upload-photo').style.display='block';
}

function uploadPhoto(){
  var input=document.getElementById('photo-input');
  if(!input.files||!input.files[0]){showToast('Selectionnez une photo','error');return;}
  var formData=new FormData();
  formData.append('photo',input.files[0]);
  var btn=document.getElementById('btn-upload-photo');
  btn.textContent='Enregistrement...';btn.disabled=true;
  fetch('index.php?route=etudiant/profil/photo',{method:'POST',body:formData})
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){
      showToast('Photo mise a jour !','success');
      btn.style.display='none';
      // Mettre à jour la photo dans la sidebar aussi
      setTimeout(function(){location.reload();},1200);
    } else {
      showToast(res.message||'Erreur','error');
      btn.textContent='Enregistrer la photo';btn.disabled=false;
    }
  }).catch(function(){showToast('Erreur reseau','error');btn.textContent='Enregistrer la photo';btn.disabled=false;});
}

// ============================================
// PROFIL — NOM
// ============================================
function sauvegarderNom(){
  var nom=document.getElementById('profil-nom').value.trim();
  if(!nom){showToast('Le nom ne peut pas etre vide','error');return;}
  fetch('index.php?route=profil/modifier',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'nom='+encodeURIComponent(nom)+'&email=<?= urlencode($email) ?>&password='
  })
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){showToast('Nom mis a jour !','success');setTimeout(function(){location.reload();},1200);}
    else{showToast(res.message||'Erreur','error');}
  }).catch(function(){showToast('Erreur reseau','error');});
}

// ============================================
// PROFIL — MOT DE PASSE
// ============================================
function togglePwd(id,btn){
  var input=document.getElementById(id);
  if(input.type==='password'){input.type='text';btn.textContent='🙈';}
  else{input.type='password';btn.textContent='👁';}
}

function evaluerForce(pwd){
  var fill=document.getElementById('pwd-fill');
  var label=document.getElementById('pwd-force-label');
  if(!pwd){fill.style.width='0';label.textContent='';return;}
  var score=0;
  if(pwd.length>=6)score++;
  if(pwd.length>=10)score++;
  if(/[A-Z]/.test(pwd))score++;
  if(/[0-9]/.test(pwd))score++;
  if(/[^A-Za-z0-9]/.test(pwd))score++;
  var configs=[
    {pct:'20%',color:'#ef4444',text:'Tres faible'},
    {pct:'40%',color:'#f97316',text:'Faible'},
    {pct:'60%',color:'#eab308',text:'Moyen'},
    {pct:'80%',color:'#22c55e',text:'Fort'},
    {pct:'100%',color:'#10b981',text:'Tres fort'},
  ];
  var c=configs[score-1]||configs[0];
  fill.style.width=c.pct;fill.style.background=c.color;
  label.textContent=c.text;label.style.color=c.color;
}

function changerMotDePasse(){
  var actuel=document.getElementById('pwd-actuel').value;
  var nouveau=document.getElementById('pwd-nouveau').value;
  var confirm=document.getElementById('pwd-confirm').value;
  if(!actuel||!nouveau||!confirm){showToast('Tous les champs sont requis','error');return;}
  if(nouveau.length<6){showToast('Le nouveau mot de passe doit avoir au moins 6 caracteres','error');return;}
  if(nouveau!==confirm){showToast('Les mots de passe ne correspondent pas','error');return;}
  fetch('index.php?route=etudiant/profil/password',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'password_actuel='+encodeURIComponent(actuel)+'&password_nouveau='+encodeURIComponent(nouveau)
  })
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){
      showToast('Mot de passe change !','success');
      document.getElementById('pwd-actuel').value='';
      document.getElementById('pwd-nouveau').value='';
      document.getElementById('pwd-confirm').value='';
      document.getElementById('pwd-fill').style.width='0';
      document.getElementById('pwd-force-label').textContent='';
    } else {
      showToast(res.message||'Erreur','error');
    }
  }).catch(function(){showToast('Erreur reseau','error');});
}

// ============================================
// ABSENCES
// ============================================
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
  var statusBox=document.getElementById('geo-status'),statusText=document.getElementById('geo-status-text'),distText=document.getElementById('geo-distance-text');
  statusBox.style.borderLeftColor='#60a5fa';statusText.textContent='Chargement de la configuration...';distText.textContent='En attente';
  fetch('index.php?route=school/config')
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success&&res.data){ECOLE_LAT=parseFloat(res.data.latitude)||14.679620;ECOLE_LNG=parseFloat(res.data.longitude)||-17.441229;RAYON_MAX=parseInt(res.data.rayon)||500;var el=document.getElementById('rayon-display');if(el)el.textContent=RAYON_MAX+' metres';}
    _demarrerGPS(statusBox,statusText,distText);
  }).catch(function(){_demarrerGPS(statusBox,statusText,distText);});
}
function _demarrerGPS(statusBox,statusText,distText){
  statusText.textContent='Verification de votre position...';distText.textContent='En attente GPS';
  if(!navigator.geolocation){statusBox.style.borderLeftColor='#f87171';statusText.textContent='Geolocalisation non supportee';distText.textContent='Votre navigateur ne supporte pas le GPS';return;}
  navigator.geolocation.getCurrentPosition(
    function(pos){
      var dist=Math.round(calculerDistance(pos.coords.latitude,pos.coords.longitude,ECOLE_LAT,ECOLE_LNG));
      if(dist<=RAYON_MAX){positionOK=true;statusBox.style.borderLeftColor='#34d399';statusText.textContent='Position validee — vous etes sur place';distText.textContent='Distance : '+dist+'m de l\'etablissement';setStatus('scan-status-ecole','Position OK — Activez la camera','success');setStatus('scan-status-perso','Position OK — Activez la camera','success');}
      else{positionOK=false;statusBox.style.borderLeftColor='#f87171';statusText.textContent='Hors du perimetre autorise';distText.textContent='Distance : '+dist+'m (max '+RAYON_MAX+'m) — Rapprochez-vous';setStatus('scan-status-ecole','Hors perimetre — pointage impossible ('+dist+'m)','error');setStatus('scan-status-perso','Hors perimetre — pointage impossible ('+dist+'m)','error');}
    },
    function(err){statusBox.style.borderLeftColor='#fbbf24';statusText.textContent='Position indisponible';distText.textContent=err.code===1?'Autorisez la localisation dans votre navigateur':'Erreur GPS';setStatus('scan-status-ecole','GPS indisponible — autorisez la localisation','warning');},
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
  scannerEcole.start({facingMode:'environment'},{fps:10,qrbox:{width:220,height:220}},function(decoded){var now=Date.now();if(now-lastScanEcole<3000)return;lastScanEcole=now;setStatus('scan-status-ecole','QR detecte ! Traitement...','info');envoyerViaQrEcole(decoded);},function(){})
  .then(function(){scanEcoleActif=true;document.getElementById('btn-start-ecole').style.display='none';document.getElementById('btn-stop-ecole').style.display='block';setStatus('scan-status-ecole','Camera active — Scannez le QR de l\'ecole','success');})
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
    if(res.success){var libelle=res.data&&res.data.mode==='depart'?'Depart':'Arrivee';setStatus('scan-status-ecole',libelle+' enregistree !','success');showToast(res.message||'Pointage enregistre !','success');arreterScanEcole();setTimeout(function(){location.reload();},2000);}
    else{setStatus('scan-status-ecole','Erreur : '+res.message,'error');showToast(res.message,'error');}
  }).catch(function(){showToast('Erreur reseau','error');});
}
function demarrerScanPerso(){
  if(!positionOK){showToast('Hors perimetre — pointage refuse','error');return;}
  if(scanPersoActif)return;
  scannerPerso=new Html5Qrcode('etu-qr-reader-perso');
  scannerPerso.start({facingMode:'environment'},{fps:10,qrbox:{width:220,height:220}},function(decoded){var now=Date.now();if(now-lastScanPerso<3000)return;lastScanPerso=now;setStatus('scan-status-perso','QR detecte ! Traitement...','info');envoyerViaQrPerso(decoded);},function(){})
  .then(function(){scanPersoActif=true;document.getElementById('btn-start-perso').style.display='none';document.getElementById('btn-stop-perso').style.display='block';setStatus('scan-status-perso','Camera active — Scannez votre QR Code','success');})
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
    if(res.success){var libelle=res.data&&res.data.mode==='depart'?'Depart':'Arrivee';setStatus('scan-status-perso',libelle+' enregistree !','success');showToast(res.message||'Pointage enregistre !','success');arreterScanPerso();setTimeout(function(){location.reload();},2000);}
    else{setStatus('scan-status-perso','Erreur : '+res.message,'error');showToast(res.message,'error');}
  }).catch(function(){showToast('Erreur reseau','error');});
}

var toastTimeout;
function showToast(msg,type){
  type=type||'info';var ex=document.querySelector('.toast');if(ex)ex.remove();clearTimeout(toastTimeout);
  var colors={success:'#34d399',error:'#f87171',info:'#60a5fa'};
  var t=document.createElement('div');t.className='toast';
  t.innerHTML='<span style="color:'+colors[type]+';font-size:16px">'+(type==='success'?'&#10003;':type==='error'?'&#10005;':'&#9432;')+'</span><span style="color:#e2e8f0">'+msg+'</span>';
  document.body.appendChild(t);toastTimeout=setTimeout(function(){t.remove();},3500);
}

// ============================================
// LOGIQUE DE LA MODALE JUSTIFIER L'ABSENCE
// ============================================
function ouvrirModalJustifier(dateAbsence) {
  document.getElementById('modal-abs-date').value = dateAbsence;
  document.getElementById('modal-date-display').textContent = dateAbsence;
  
  // Reset le formulaire à l'ouverture
  document.getElementById('modal-justif-form').reset();
  
  var modal = document.getElementById('modal-justifier');
  modal.classList.remove('hidden');
}

function fermerModalJustifier() {
  var modal = document.getElementById('modal-justifier');
  modal.classList.add('hidden');
}

function soumettreJustificationModal(e) {
  e.preventDefault();
  
  var date = document.getElementById('modal-abs-date').value;
  var motif = document.getElementById('modal-abs-motif').value;
  var details = document.getElementById('modal-abs-details').value;
  var docFile = document.getElementById('modal-abs-document').files[0];
  
  if(!motif) {
    showToast('Veuillez choisir un motif', 'error');
    return;
  }
  
  var formData = new FormData();
  // On mappe les variables attendues par le LeaveController (type, start_date, end_date, etc.)
  formData.append('type', motif);
  formData.append('start_date', date);
  formData.append('end_date', date);
  formData.append('date_absence', date);
  formData.append('reason', details);
  if(docFile) {
    formData.append('document', docFile);
  }
  
  var btn = document.getElementById('modal-abs-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Envoi en cours...';
  
  fetch('index.php?route=conges/soumettre', { method: 'POST', body: formData })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    if(res.success) {
      showToast('Justificatif envoyé à l\'administrateur !', 'success');
      fermerModalJustifier();
      // Petit rechargement pour rafraîchir l'affichage
      setTimeout(function() { location.reload(); }, 1500);
    } else {
      showToast(res.message || 'Erreur', 'error');
      btn.disabled = false;
      btn.textContent = 'Envoyer à l\'admin';
    }
  })
  .catch(function() {
    showToast('Erreur réseau', 'error');
    btn.disabled = false;
    btn.textContent = 'Envoyer à l\'admin';
  });
}

function soumettreAbsence(e) {
  e.preventDefault();

  var motif = document.getElementById('abs-motif').value;
  var date = document.getElementById('abs-date').value;
  var details = document.getElementById('abs-details').value;

  if (!motif || !date) {
    showToast('Veuillez remplir tous les champs obligatoires.', 'error');
    return;
  }

  // Préparation des données à envoyer au contrôleur
  var formData = new FormData();
  formData.append('type', motif);
  formData.append('start_date', date);
  formData.append('end_date', date);
  formData.append('reason', details);
  // Pas de paramètre 'date_absence' ici car c'est une absence FUTURE

  var btn = document.getElementById('abs-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Envoi en cours...';

  // Envoi des données en AJAX vers le LeaveController
  fetch('index.php?route=conges/soumettre', { 
    method: 'POST', 
    body: formData 
  })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    if (res.success) {
      showToast('Demande d\'absence future envoyée à l\'administrateur !', 'success');
      fermerFormulaireAbsence();
      
      // Rechargement rapide de la page pour mettre à jour la liste
      setTimeout(function() { 
        location.reload(); 
      }, 1500);
    } else {
      showToast(res.message || 'Erreur lors de l\'envoi', 'error');
      btn.disabled = false;
      btn.textContent = 'Envoyer la demande';
    }
  })
  .catch(function() {
    showToast('Erreur réseau. Impossible de joindre le serveur.', 'error');
    btn.disabled = false;
    btn.textContent = 'Envoyer la demande';
  });
}
</script>
</body>
</html>

<?php
/*
|=============================================================
| ROUTES À AJOUTER dans votre index.php / routeur :
|=============================================================
|
|  'etudiant/profil/photo'    => [EtudiantController::class, 'uploadPhoto']
|  'etudiant/profil/password' => [EtudiantController::class, 'changerPassword']
|
|=============================================================
| MÉTHODES À AJOUTER dans EtudiantController.php :
|=============================================================

    // ============================================
    // UPLOAD PHOTO DE PROFIL (AJAX)
    // ============================================
    public function uploadPhoto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $userId = $_SESSION['user']['id'] ?? '';
        if (empty($userId)) {
            $this->json(false, 'Non connecte');
            return;
        }

        if (empty($_FILES['photo']['name'])) {
            $this->json(false, 'Aucun fichier selectionne');
            return;
        }

        $file    = $_FILES['photo'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $this->json(false, 'Format non supporte. Utilisez JPG, PNG ou WebP');
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(false, 'Image trop lourde (max 5MB)');
            return;
        }

        // Créer le dossier si nécessaire
        $uploadDir = __DIR__ . '/../../public/uploads/photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Supprimer l'ancienne photo si elle existe
        $user = $this->userModel->findById($userId);
        if ($user && !empty($user['photo'])) {
            $oldFile = $uploadDir . $user['photo'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Nouveau nom de fichier unique
        $nomFichier = $userId . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $nomFichier;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->json(false, 'Erreur lors de l\'enregistrement');
            return;
        }

        // Sauvegarder en base
        $stmt = $this->db->prepare("UPDATE users SET photo = ? WHERE id = ?");
        $stmt->execute([$nomFichier, $userId]);

        // Mettre à jour la session
        $_SESSION['user']['photo'] = $nomFichier;

        $this->json(true, 'Photo mise a jour avec succes', ['photo' => $nomFichier]);
    }

    // ============================================
    // CHANGER MOT DE PASSE (AJAX)
    // ============================================
    public function changerPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(false, 'Methode non autorisee');
            return;
        }

        $userId = $_SESSION['user']['id'] ?? '';
        if (empty($userId)) {
            $this->json(false, 'Non connecte');
            return;
        }

        $pwdActuel  = $_POST['password_actuel']  ?? '';
        $pwdNouveau = $_POST['password_nouveau']  ?? '';

        if (empty($pwdActuel) || empty($pwdNouveau)) {
            $this->json(false, 'Tous les champs sont requis');
            return;
        }

        if (strlen($pwdNouveau) < 6) {
            $this->json(false, 'Le nouveau mot de passe doit avoir au moins 6 caracteres');
            return;
        }

        // Vérifier l'ancien mot de passe
        $user = $this->userModel->findById($userId);
        if (!$user || !password_verify($pwdActuel, $user['password_hash'])) {
            $this->json(false, 'Mot de passe actuel incorrect');
            return;
        }

        // Mettre à jour
        $hash = password_hash($pwdNouveau, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);

        $this->json(true, 'Mot de passe change avec succes');
    }

|=============================================================
| COLONNE À AJOUTER dans la table users (si pas déjà fait) :
|=============================================================
|
|  ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL DEFAULT NULL;
|
|=============================================================
*/