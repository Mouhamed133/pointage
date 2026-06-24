<?php
$nom   = $_SESSION['user']['nom']   ?? 'Admin';
$email = $_SESSION['user']['email'] ?? '';
$role  = $_SESSION['user']['role']  ?? 'admin';
$presents          = $presents          ?? 0;
$absents           = $absents           ?? 0;
$retards           = $retards           ?? 0;
$totalEtudiants    = $totalEtudiants    ?? 0;
$derniersPointages = $derniersPointages ?? [];
$statsData     = json_encode(['presents'=>$presents,'absents'=>$absents,'retards'=>$retards,'totalEtudiants'=>$totalEtudiants]);
$pointagesData = json_encode($derniersPointages);

// ============================================================
// DEPARTEMENTS TELLYTECH
// ============================================================
$departements = ['Dev','Marketing','Business','Langue','Développement','Data & IA','Bureautique'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PointagePro</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Space Grotesk',sans-serif;background:#0a0e1a;color:#e2e8f0;min-height:100vh}
    .mono{font-family:'JetBrains Mono',monospace}
    .sidebar{width:260px;background:#0f1629;border-right:1px solid #1e2a4a;height:100vh;position:fixed;left:0;top:0;z-index:100;transition:transform .3s ease;display:flex;flex-direction:column}
    .sidebar.collapsed{transform:translateX(-260px)}
    .main-content{margin-left:260px;min-height:100vh;transition:margin .3s ease}
    .main-content.expanded{margin-left:0}
    .card{background:#0f1629;border:1px solid #1e2a4a;border-radius:16px}
    .badge-present{background:#0d2a1e;color:#34d399;border:1px solid #064e3b}
    .badge-absent{background:#2d1414;color:#f87171;border:1px solid #7f1d1d}
    .badge-retard{background:#2d2006;color:#fbbf24;border:1px solid #78350f}
    .badge-pending{background:#1a2333;color:#60a5fa;border:1px solid #1e3a5f}
    @keyframes pulse-ring{0%{transform:scale(.95);box-shadow:0 0 0 0 rgba(52,211,153,.4)}70%{transform:scale(1);box-shadow:0 0 0 10px rgba(52,211,153,0)}100%{transform:scale(.95);box-shadow:0 0 0 0 rgba(52,211,153,0)}}
    @keyframes slide-in{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    .pulse-dot{animation:pulse-ring 2s ease-in-out infinite}
    .nav-item{display:flex;align-items:center;gap:12px;padding:10px 16px;border-radius:10px;cursor:pointer;transition:all .2s;color:#94a3b8;font-size:14px;font-weight:500;text-decoration:none}
    .nav-item:hover{background:#1e2a4a;color:#e2e8f0}
    .nav-item.active{background:#1a3a5c;color:#34d399}
    .nav-item svg{width:18px;height:18px;flex-shrink:0}
    .progress-bar{height:6px;background:#1e2a4a;border-radius:3px;overflow:hidden}
    .progress-fill{height:100%;border-radius:3px;transition:width 1s ease}
    .data-table th{background:#0a0e1a;color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;padding:12px 16px}
    .data-table td{padding:14px 16px;border-top:1px solid #1e2a4a;font-size:14px}
    .data-table tr:hover td{background:rgba(30,42,74,.4)}
    input,select,textarea{background:#0a0e1a;border:1px solid #1e2a4a;border-radius:10px;color:#e2e8f0;padding:10px 14px;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s;width:100%}
    input:focus,select:focus,textarea:focus{border-color:#34d399}
    .btn-primary{background:#059669;color:white;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-primary:hover{background:#047857;transform:translateY(-1px)}
    .btn-secondary{background:#1e2a4a;color:#e2e8f0;border:1px solid #2d3f6a;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;transition:all .2s;font-family:inherit}
    .btn-secondary:hover{background:#253354}
    .btn-danger{background:#7f1d1d;color:#fca5a5;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;font-family:inherit}
    .btn-danger:hover{background:#991b1b}
    .btn-success{background:#064e3b;color:#34d399;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;font-family:inherit}
    .btn-success:hover{background:#065f46}
    .stat-accent-green{border-left:3px solid #34d399}
    .stat-accent-red{border-left:3px solid #f87171}
    .stat-accent-yellow{border-left:3px solid #fbbf24}
    .stat-accent-blue{border-left:3px solid #60a5fa}
    .avatar{border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0}
    .chart-bar{background:#1e2a4a;border-radius:4px 4px 0 0;position:relative;cursor:pointer;min-width:28px}
    .chart-bar:hover{background:#253354}
    .chart-bar .bar-fill{background:linear-gradient(180deg,#34d399,#059669);border-radius:4px 4px 0 0;position:absolute;bottom:0;left:0;right:0}
    .toast{position:fixed;bottom:24px;right:24px;background:#0f1629;border:1px solid #1e2a4a;border-radius:12px;padding:14px 18px;z-index:9999;animation:slide-in .3s ease;display:flex;align-items:center;gap:10px;font-size:14px;box-shadow:0 8px 32px rgba(0,0,0,.4)}
    .modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;display:flex;align-items:center;justify-content:center}
    .modal{background:#0f1629;border:1px solid #1e2a4a;border-radius:20px;padding:32px;width:500px;max-width:90vw;max-height:85vh;overflow-y:auto}
    ::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:#0a0e1a}::-webkit-scrollbar-thumb{background:#1e2a4a;border-radius:3px}
    .view{display:none}
    #qr-reader{width:100%;border-radius:12px;overflow:hidden;background:#000}
    #qr-reader video{border-radius:12px}
    #qr-reader__scan_region{border-radius:12px}
    #qr-reader__dashboard{background:#0f1629;padding:12px;border-radius:0 0 12px 12px}
    #qr-reader__dashboard button{background:#059669;color:white;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-family:inherit;font-size:13px;margin:4px}
    #qr-reader__dashboard select{background:#0a0e1a;color:#e2e8f0;border:1px solid #1e2a4a;padding:6px;border-radius:8px;font-size:13px}
    .scanner-status{padding:12px 16px;border-radius:10px;font-size:14px;text-align:center;font-weight:500}
    .scanner-status.scanning{background:#0d2a1e;color:#34d399;border:1px solid #064e3b}
    .scanner-status.idle{background:#1a2333;color:#60a5fa;border:1px solid #1e3a5f}
    .scanner-status.success{background:#0d2a1e;color:#34d399;border:1px solid #064e3b}
    .scanner-status.error{background:#2d1414;color:#f87171;border:1px solid #7f1d1d}
    .action-btn-group{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .view.active{display:block;animation:slide-in .4s ease forwards}
    .sidebar-nav{flex:1;overflow-y:auto;padding:12px}
    @media (max-width: 768px) {
      .sidebar{transform:translateX(-260px)}
      .sidebar.mobile-open{transform:translateX(0);box-shadow:4px 0 20px rgba(0,0,0,.5)}
      .main-content{margin-left:0 !important}
      .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}
      .sidebar-overlay.active{display:block}
      #page-title{font-size:15px}
      .grid.grid-cols-2.lg\:grid-cols-4{grid-template-columns:repeat(2,1fr);gap:10px}
      .card.p-5.stat-accent-green,.card.p-5.stat-accent-red,.card.p-5.stat-accent-yellow,.card.p-5.stat-accent-blue{padding:12px 14px}
      .text-3xl{font-size:1.5rem}
      .grid.grid-cols-1.lg\:grid-cols-3{grid-template-columns:1fr}
      .lg\:col-span-2{grid-column:span 1}
      .overflow-x-auto{overflow-x:auto;-webkit-overflow-scrolling:touch}
      .data-table{min-width:500px}
      .flex.flex-wrap.gap-3 input[type=date],.flex.flex-wrap.gap-3 select{width:auto;min-width:120px;max-width:160px}
      .grid.grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3{grid-template-columns:1fr}
      .data-table th,.data-table td{padding:8px 10px;font-size:12px}
      .modal{width:95vw;padding:20px}
      .p-6{padding:16px}
      .p-5{padding:14px}
      .grid.grid-cols-1.md\:grid-cols-3{grid-template-columns:1fr}
      .max-w-md,.max-w-sm,.max-w-lg{max-width:100%}
    }
    @media (max-width: 480px) {
      .grid.grid-cols-2.lg\:grid-cols-4{grid-template-columns:repeat(2,1fr)}
      #page-title{font-size:13px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
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
        <p class="text-[11px] text-emerald-400 mono">v1.0 &mdash; <?= ucfirst($role) ?></p>
      </div>
    </div>
  </div>
  <div class="sidebar-nav">
    <p class="text-[11px] uppercase tracking-widest text-slate-600 px-3 mb-2 mt-2">Navigation</p>
    <nav id="main-nav" class="space-y-1">
      <div class="nav-item active" data-view="dashboard">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Tableau de bord
      </div>
      <div class="nav-item" data-view="pointage">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Pointage QR Code
      </div>
      <div class="nav-item" data-view="presences">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        Presences
      </div>
      <div class="nav-item" data-view="etudiants">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg>
        Etudiants
      </div>
      <?php if (in_array($role, ['admin', 'manager'])): ?>
      <div class="nav-item" data-view="validation">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Invitations en attente
      </div>
      <?php endif; ?>
      <div class="nav-item" data-view="conges">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Gestion Absences
      </div>
      <div class="nav-item" data-view="rapports">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Rapports
      </div>
      <div class="nav-item" data-view="qrecole">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        QR Code Ecole
      </div>
      <div class="nav-item" data-view="audit">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Audit Log
      </div>
    </nav>
    <div class="mt-6">
      <p class="text-[11px] uppercase tracking-widest text-slate-600 px-3 mb-2">Compte</p>
      <div class="nav-item" data-view="profil">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mon Profil
      </div>
      <div class="nav-item" style="cursor:pointer" onclick="window.location.href='index.php?route=logout'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Deconnexion
      </div>
    </div>
  </div>
  <div class="p-4 border-t border-[#1e2a4a]">
    <div class="flex items-center gap-3">
      <div class="avatar w-9 h-9 bg-emerald-900 text-emerald-300 text-sm"><?= strtoupper(substr($nom, 0, 2)) ?></div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-600 text-white truncate"><?= htmlspecialchars($nom) ?></p>
        <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($email) ?></p>
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
      <div id="page-title" class="text-lg font-700 text-white">Tableau de bord</div>
    </div>
    <div class="flex items-center gap-3">
      <div class="mono text-xs text-slate-400" id="clock-display">--:--:--</div>
    </div>
  </div>

  <div class="p-6">

    <!-- DASHBOARD -->
    <div id="view-dashboard" class="view active">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-5 stat-accent-green"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Presents Aujourd'hui</p><p class="text-3xl font-700 text-white mono" id="stat-present">0</p><p class="text-xs text-emerald-400 mt-1">Aujourd'hui</p></div>
        <div class="card p-5 stat-accent-red"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Absents</p><p class="text-3xl font-700 text-white mono" id="stat-absent">0</p><p class="text-xs text-red-400 mt-1">Aujourd'hui</p></div>
        <div class="card p-5 stat-accent-yellow"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Retards</p><p class="text-3xl font-700 text-white mono" id="stat-retard">0</p><p class="text-xs text-yellow-400 mt-1">Aujourd'hui</p></div>
        <div class="card p-5 stat-accent-blue"><p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Total Etudiants</p><p class="text-3xl font-700 text-white mono" id="stat-total">0</p><p class="text-xs text-blue-400 mt-1">Actifs</p></div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-5 lg:col-span-2">
          <div class="flex items-center justify-between mb-5"><p class="font-600 text-white">Presences cette semaine</p><span class="text-xs text-slate-500 mono"><?= date('M Y') ?></span></div>
          <div class="flex items-end gap-2 h-32" id="weekly-chart"></div>
          <div class="flex justify-between mt-2"><span class="text-xs text-slate-500">Lun</span><span class="text-xs text-slate-500">Mar</span><span class="text-xs text-slate-500">Mer</span><span class="text-xs text-slate-500">Jeu</span><span class="text-xs text-slate-500">Ven</span><span class="text-xs text-slate-500">Sam</span></div>
        </div>
        <div class="card p-5">
          <p class="font-600 text-white mb-4">Taux de presence</p>
          <div class="flex flex-col gap-3" id="taux-container"></div>
          <div class="mt-5 pt-4 border-t border-[#1e2a4a]"><p class="text-xs text-slate-500 mb-1">Total Etudiants</p><p class="text-2xl font-700 text-white mono" id="total-display">0</p></div>
        </div>
      </div>
      <div class="card">
        <div class="p-5 border-b border-[#1e2a4a] flex items-center justify-between">
          <p class="font-600 text-white">Activite recente</p>
          <button class="btn-secondary text-xs px-3 py-1.5" onclick="switchView('presences')">Voir tout</button>
        </div>
        <div id="recent-tbody" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4"></div>
      </div>
    </div>

    <!-- POINTAGE QR CODE -->
    <div id="view-pointage" class="view">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
          <h2 class="font-700 text-white text-lg mb-1">Scanner QR Code</h2>
          <p class="text-slate-400 text-sm mb-4">Utilisez la camera pour scanner le QR Code de l'etudiant</p>
          <div id="scanner-status" class="scanner-status idle mb-4">Pret a scanner</div>
          <div id="qr-reader" class="mb-4"></div>
          <div class="action-btn-group mb-4">
            <button id="btn-start-scan" onclick="demarrerScanner()" class="btn-primary py-3">Demarrer Camera</button>
            <button id="btn-stop-scan" onclick="arreterScanner()" class="btn-secondary py-3" style="display:none">Arreter Camera</button>
          </div>
          <div class="border-t border-[#1e2a4a] pt-4">
            <p class="text-sm text-slate-400 mb-3">Ou saisie manuelle :</p>
            <div class="flex gap-2">
              <input type="text" id="manual-token" placeholder="Coller le token ici...">
              <button onclick="pointerManuellement()" class="btn-primary px-4 whitespace-nowrap">Pointer</button>
            </div>
          </div>
        </div>
        <div class="card p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-700 text-white text-lg">Derniers pointages</h2>
            <span id="scan-count" class="text-xs badge-present px-2 py-0.5 rounded-full">0 aujourd'hui</span>
          </div>
          <div class="space-y-3" id="scan-log">
            <div class="text-center py-8">
              <svg viewBox="0 0 24 24" fill="none" stroke="#1e2a4a" stroke-width="1.5" class="w-12 h-12 mx-auto mb-2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
              <p class="text-slate-500 text-sm">Aucun pointage recent</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PRESENCES -->
    <div id="view-presences" class="view">
      <div class="flex flex-wrap gap-3 mb-5">
        <input type="date" id="filter-date" class="w-auto" value="<?= date('Y-m-d') ?>" onchange="chargerPresences()">
        <select id="filter-dept" class="w-auto" onchange="chargerPresences()">
          <option value="">Tous departements</option>
          <?php foreach($departements as $d): ?>
          <option><?= htmlspecialchars($d) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="filter-statut" class="w-auto" onchange="chargerPresences()">
          <option value="">Tous statuts</option>
          <option value="present">Present</option><option value="retard">Retard</option><option value="absence">Absent</option>
        </select>
        <button onclick="chargerPresences()" class="btn-secondary text-sm">Actualiser</button>
        <button onclick="showToast('Export Excel...','info')" class="btn-secondary text-sm">Export Excel</button>
        <button onclick="showToast('Export PDF...','info')" class="btn-secondary text-sm">Export PDF</button>
      </div>
      <div class="card">
        <div id="presences-tbody" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
          <p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucune donnee</p>
        </div>
        <!-- PAGINATION : visible seulement si plusieurs pages (géré en JS) -->
        <div id="presences-pagination" class="p-4 flex items-center justify-between border-t border-[#1e2a4a]" style="display:none!important">
          <button id="presences-prev" onclick="presencesPage(-1)" class="btn-secondary text-xs px-3 py-1.5">Precedent</button>
          <span id="presences-page-info" class="text-xs text-slate-500"></span>
          <button id="presences-next" onclick="presencesPage(1)" class="btn-secondary text-xs px-3 py-1.5">Suivant</button>
        </div>
      </div>
    </div>

    <!-- ETUDIANTS -->
    <div id="view-etudiants" class="view">
      <div class="flex justify-between mb-5">
        <input type="text" placeholder="Rechercher..." class="max-w-xs" id="search-etu" oninput="chargerEtudiants()">
        <div class="flex gap-2">
          <button onclick="openModalImport()" class="btn-secondary">Importer Excel</button>
          <button onclick="openModal('addEtudiant')" class="btn-primary">+ Ajouter etudiant</button>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="etudiants-grid">
        <p class="text-slate-500 text-sm col-span-3 text-center py-8">Chargement...</p>
      </div>
    </div>

    <!-- VALIDATION -->
    <?php if (in_array($role, ['admin', 'manager'])): ?>
    <div id="view-validation" class="view">
      <div class="card">
        <div class="p-5 border-b border-[#1e2a4a] flex items-center justify-between">
          <p class="font-600 text-white">Invitations en attente d'activation</p>
          <button onclick="chargerValidation()" class="btn-secondary text-xs px-3 py-1.5">Actualiser</button>
        </div>
        <div id="validation-list" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4"><p class="text-slate-500 text-sm text-center py-8 col-span-full">Chargement...</p></div>
        <!-- PAGINATION : visible seulement si plusieurs pages (géré en JS) -->
        <div id="valid-pagination" class="p-4 flex items-center justify-between border-t border-[#1e2a4a]" style="display:none!important">
          <button id="valid-prev" onclick="validationPage(-1)" class="btn-secondary text-xs px-3 py-1.5">Precedent</button>
          <span id="valid-page-info" class="text-xs text-slate-500"></span>
          <button id="valid-next" onclick="validationPage(1)" class="btn-secondary text-xs px-3 py-1.5">Suivant</button>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- GESTION ABSENCES -->
    <div id="view-conges" class="view">
      <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="card p-4 stat-accent-yellow"><p class="text-xs text-slate-500 uppercase tracking-wider mb-1">En attente</p><p class="text-2xl font-700 text-white mono" id="conge-count-attente">0</p></div>
        <div class="card p-4 stat-accent-green"><p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Approuves</p><p class="text-2xl font-700 text-white mono" id="conge-count-approuve">0</p></div>
        <div class="card p-4 stat-accent-red"><p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Refuses</p><p class="text-2xl font-700 text-white mono" id="conge-count-refuse">0</p></div>
      </div>
      <div class="flex flex-wrap gap-3 mb-5">
        <select id="conge-filter-statut" class="w-auto" onchange="chargerConges()">
          <option value="">Tous les statuts</option><option value="en_attente">En attente</option><option value="approuve">Approuves</option><option value="refuse">Refuses</option>
        </select>
        <button onclick="chargerConges()" class="btn-secondary text-sm">Actualiser</button>
        <button onclick="ouvrirModalConge()" class="btn-primary text-sm">+ Nouvelle absence</button>
      </div>
      <div class="card">
        <div id="conges-tbody" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
          <p class="text-slate-500 text-sm text-center py-8 col-span-full">Chargement...</p>
        </div>
        <!-- PAGINATION : visible seulement si plusieurs pages (géré en JS) -->
        <div id="conges-pagination" class="p-4 flex items-center justify-between border-t border-[#1e2a4a]" style="display:none!important">
          <button id="conges-prev" onclick="congesPage(-1)" class="btn-secondary text-xs px-3 py-1.5">Precedent</button>
          <span id="conges-page-info" class="text-xs text-slate-500"></span>
          <button id="conges-next" onclick="congesPage(1)" class="btn-secondary text-xs px-3 py-1.5">Suivant</button>
        </div>
      </div>
    </div>

    <!-- RAPPORTS -->
    <div id="view-rapports" class="view">
      <div class="card p-4 mb-5 flex items-center gap-4">
        <span class="text-sm text-slate-400">Mois :</span>
        <input type="month" id="rapport-mois" value="<?= date('Y-m') ?>" class="w-auto">
        <span class="text-xs text-slate-500">Utilise pour tous les exports</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-5 hover:border-emerald-700 transition-colors">
          <div class="w-10 h-10 bg-emerald-900/50 rounded-xl flex items-center justify-center mb-3"><svg viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" class="w-5 h-5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
          <p class="font-600 text-white">Rapport Mensuel</p><p class="text-xs text-slate-500 mt-1">Presences, absences, retards par mois</p><p class="text-xs text-emerald-400 mt-1">Format PDF</p>
          <button class="btn-primary text-xs mt-4 px-3 py-1.5 w-full" onclick="exporterRapport('mensuel')">Generer PDF</button>
        </div>
        <div class="card p-5 hover:border-blue-700 transition-colors">
          <div class="w-10 h-10 bg-blue-900/50 rounded-xl flex items-center justify-center mb-3"><svg viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" class="w-5 h-5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg></div>
          <p class="font-600 text-white">Fiche de Presence</p><p class="text-xs text-slate-500 mt-1">Export Excel de toutes les presences</p><p class="text-xs text-blue-400 mt-1">Format XLSX</p>
          <div class="mt-3">
            <select id="rapport-dept" class="text-xs mb-2">
              <option value="">Tous departements</option>
              <?php foreach($departements as $d): ?>
              <option><?= htmlspecialchars($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn-secondary text-xs px-3 py-1.5 w-full" onclick="exporterRapport('excel')">Export Excel</button>
        </div>
        <div class="card p-5 hover:border-purple-700 transition-colors">
          <div class="w-10 h-10 bg-purple-900/50 rounded-xl flex items-center justify-center mb-3"><svg viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2" class="w-5 h-5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
          <p class="font-600 text-white">Rapport Conges</p><p class="text-xs text-slate-500 mt-1">Historique des conges et absences</p><p class="text-xs text-purple-400 mt-1">Format PDF</p>
          <button class="btn-secondary text-xs mt-4 px-3 py-1.5 w-full" onclick="exporterRapport('conges')">Export PDF</button>
        </div>
      </div>
      <div class="card p-6">
        <p class="font-600 text-white mb-5">Statistiques mensuelles <?= date('Y') ?></p>
        <div class="flex items-end gap-1 h-40" id="monthly-chart"></div>
        <div class="flex justify-between mt-2 text-xs text-slate-500"><span>Jan</span><span>Fev</span><span>Mar</span><span>Avr</span><span>Mai</span><span>Jun</span><span>Jul</span><span>Aou</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span></div>
      </div>
    </div>

    <!-- QR CODE ECOLE -->
    <div id="view-qrecole" class="view">
      <div class="max-w-3xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
        <div class="card p-4 text-center">
          <div class="w-12 h-12 bg-emerald-900/50 rounded-2xl flex items-center justify-center mx-auto mb-3"><svg viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" class="w-6 h-6"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
          <h2 class="font-700 text-white text-lg mb-1">QR Code de l'Etablissement</h2>
          <p class="text-sm text-slate-400 mb-4">Affichez ce QR Code a l'entree. Les etudiants le scanneront pour pointer.</p>
          <div id="school-qr-container" class="inline-block p-3 bg-white rounded-2xl mb-3"></div>
          <p id="school-qr-token" class="mono text-xs text-slate-500 break-all px-4 mb-2"></p>
          <p id="school-qr-label" class="text-xs text-emerald-400 mb-4"></p>
          <p id="school-qr-url" class="mono text-[10px] text-slate-600 break-all px-4 mb-4"></p>
          <div class="flex gap-3">
            <button onclick="imprimerQrEcole()" class="btn-secondary flex-1 py-2.5 text-sm">Imprimer</button>
            <button onclick="regenererQrEcole()" class="btn-danger flex-1 py-2.5 text-sm">Regenerer QR</button>
          </div>
        </div>
        <div class="card p-5">
          <p class="font-600 text-white mb-1">Configuration GPS</p>
          <p class="text-xs text-slate-500 mb-4">Position et perimetre autorise pour le pointage des etudiants</p>
          <div class="space-y-3">
            <div><label class="text-xs text-slate-500 block mb-1">Latitude</label><input type="text" id="config-latitude" placeholder=""></div>
            <div><label class="text-xs text-slate-500 block mb-1">Longitude</label><input type="text" id="config-longitude" placeholder=""></div>
            <div><label class="text-xs text-slate-500 block mb-1">Rayon autorise (metres)</label><input type="number" id="config-rayon" placeholder=""></div>
            <button onclick="enregistrerConfigGps()" class="btn-primary w-full py-2.5">Enregistrer la position</button>
          </div>
        </div>
      </div>
    </div>

    <!-- AUDIT LOG -->
    <div id="view-audit" class="view">
      <div class="flex flex-wrap gap-3 mb-5">
        <input type="text" id="audit-search" placeholder="Rechercher un utilisateur, une action..." class="max-w-sm" oninput="chargerAudit()">
        <select id="audit-filter-action" class="w-auto" onchange="chargerAudit()" style="width:180px"><option value="">Toutes les actions</option></select>
        <button onclick="chargerAudit()" class="btn-secondary text-sm">Actualiser</button>
      </div>
      <div class="card">
        <div class="p-5 border-b border-[#1e2a4a] flex items-center justify-between">
          <div>
            <p class="font-600 text-white">Journal des Actions Systeme</p>
            <p class="text-[10px] text-slate-600 mt-0.5">Actualisation automatique toutes les 30 min</p>
          </div>
          <span id="audit-total" class="text-xs text-slate-500">0 entrees</span>
        </div>
        <div id="audit-list" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-4"><p class="text-slate-500 text-sm text-center py-8 col-span-full">Chargement...</p></div>
        <!-- PAGINATION : visible seulement si plusieurs pages (géré en JS) -->
        <div id="audit-pagination" class="p-4 flex items-center justify-between border-t border-[#1e2a4a]" style="display:none!important">
          <button id="audit-prev" onclick="auditPage(-1)" class="btn-secondary text-xs px-3 py-1.5">Precedent</button>
          <span id="audit-page-info" class="text-xs text-slate-500">Page 1</span>
          <button id="audit-next" onclick="auditPage(1)" class="btn-secondary text-xs px-3 py-1.5">Suivant</button>
        </div>
      </div>
    </div>

    <!-- PROFIL -->
    <div id="view-profil" class="view">
      <div class="max-w-lg">
        <div class="card p-6">
          <div class="flex items-center gap-4 mb-6">
            <div class="avatar w-16 h-16 bg-emerald-900 text-emerald-300 text-xl"><?= strtoupper(substr($nom, 0, 2)) ?></div>
            <div>
              <p class="font-700 text-white text-lg"><?= htmlspecialchars($nom) ?></p>
              <p class="text-sm text-slate-400"><?= htmlspecialchars($email) ?></p>
              <span class="text-xs badge-present px-2 py-0.5 rounded-full"><?= ucfirst($role) ?></span>
            </div>
          </div>
          <div class="space-y-4">
            <div><label class="text-xs text-slate-500 block mb-1">Nom complet</label><input type="text" id="profil-nom" value="<?= htmlspecialchars($nom) ?>"></div>
            <div><label class="text-xs text-slate-500 block mb-1">Email</label><input type="email" id="profil-email" value="<?= htmlspecialchars($email) ?>"></div>
            <div><label class="text-xs text-slate-500 block mb-1">Nouveau mot de passe <span class="text-slate-600">(laisser vide pour ne pas changer)</span></label><input type="password" id="profil-password" placeholder="..."></div>
            <button class="btn-primary w-full py-3" onclick="sauvegarderProfil()">Sauvegarder</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<div id="modal-bg" class="modal-bg hidden" onclick="if(event.target.id==='modal-bg')closeModal()">
  <div id="modal-content" class="modal"></div>
</div>

<script>
// ============================================================
// DEPARTEMENTS TELLYTECH (utilisés dans les modals JS)
// ============================================================
var DEPARTEMENTS = <?= json_encode($departements) ?>;

function deptOptions(selected) {
  return DEPARTEMENTS.map(function(d){
    return '<option value="'+d+'"'+(d===selected?' selected':'')+'>'+d+'</option>';
  }).join('');
}

var phpStats     = <?= $statsData ?>;
var phpPointages = <?= $pointagesData ?>;
var scanCount    = 0;
var html5QrCode  = null;
var scannerActif = false;
var lastScanTime = 0;
var geoPosition  = null;

function capturerPosition(){
  if(!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(
    function(pos){ geoPosition = {lat: pos.coords.latitude, lng: pos.coords.longitude}; },
    function(){ geoPosition = null; },
    {enableHighAccuracy:true, timeout:8000, maximumAge:60000}
  );
}

function updateClock(){document.getElementById('clock-display').textContent=new Date().toLocaleTimeString('fr-FR');}
setInterval(updateClock,1000);updateClock();

function toggleSidebar(){
  var sidebar=document.getElementById('sidebar');var overlay=document.getElementById('sidebar-overlay');var isMobile=window.innerWidth<=768;
  if(isMobile){sidebar.classList.toggle('mobile-open');overlay.classList.toggle('active');}
  else{sidebar.classList.toggle('collapsed');document.getElementById('main-content').classList.toggle('expanded');}
}
function closeSidebarMobile(){
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebar-overlay').classList.remove('active');
}
document.addEventListener('DOMContentLoaded',function(){
  document.getElementById('main-nav').addEventListener('click',function(){
    if(window.innerWidth<=768) closeSidebarMobile();
  });
});

function switchView(name){
  if(name!=='pointage'&&scannerActif) arreterScanner();
  document.querySelectorAll('.view').forEach(function(v){v.classList.remove('active');});
  document.querySelectorAll('.nav-item').forEach(function(n){n.classList.remove('active');});
  var v=document.getElementById('view-'+name);if(v)v.classList.add('active');
  var n=document.querySelector('[data-view="'+name+'"]');if(n)n.classList.add('active');
  var titles={dashboard:'Tableau de bord',pointage:'Pointage QR Code',presences:'Presences',etudiants:'Etudiants',validation:'Invitations en attente',conges:'Gestion Absences',rapports:'Rapports',qrecole:'QR Code Ecole',audit:'Audit Log',profil:'Mon Profil'};
  document.getElementById('page-title').textContent=titles[name]||'';
  if(name==='rapports') renderMonthlyChart();
  if(name==='etudiants') chargerEtudiants();
  if(name==='validation') chargerValidation();
  if(name==='presences') chargerPresences();
  if(name==='conges') chargerConges();
  if(name==='qrecole') chargerQrEcole();
  if(name==='audit'){
    chargerAudit();
    if(auditAutoRefresh) clearInterval(auditAutoRefresh);
    auditAutoRefresh=setInterval(chargerAudit,30*60*1000);
  } else if(auditAutoRefresh){
    clearInterval(auditAutoRefresh);
    auditAutoRefresh=null;
  }
}
document.getElementById('main-nav').addEventListener('click',function(e){
  var i=e.target.closest('[data-view]');if(i)switchView(i.dataset.view);
});

function getInitials(name){if(!name)return'??';return name.split(' ').map(function(n){return n[0];}).join('').toUpperCase().slice(0,2);}
function getBadge(type){
  var map={present:['badge-present','Present'],retard:['badge-retard','Retard'],absence:['badge-absent','Absent'],en_attente:['badge-pending','En attente'],approuve:['badge-present','Approuve'],refuse:['badge-absent','Refuse']};
  var item=map[type]||['badge-pending',type];
  return '<span class="text-xs '+item[0]+' px-2.5 py-1 rounded-full">'+item[1]+'</span>';
}

// ============================================================
// HELPER : afficher/masquer la barre de pagination
// ============================================================
function togglePagination(id, show) {
  var el = document.getElementById(id);
  if (!el) return;
  el.style.setProperty('display', show ? 'flex' : 'none', 'important');
}

function renderGraphique(){
  var data=[18,22,20,24,0,8];var max=Math.max.apply(null,data.concat([1]));
  document.getElementById('weekly-chart').innerHTML=data.map(function(v,i){
    return '<div class="flex-1 flex flex-col items-center gap-1"><span class="text-xs text-slate-500 mono">'+v+'</span><div class="chart-bar w-full" style="height:'+(v/max*100)+'%"><div class="bar-fill" style="height:'+(i===4?'100%':'60%')+'"></div></div></div>';
  }).join('');
  var grid=document.getElementById('recent-tbody');
  if(!phpPointages||phpPointages.length===0){grid.innerHTML='<p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucun pointage aujourd\'hui</p>';return;}
  grid.innerHTML=phpPointages.map(function(p){
    return '<div class="card p-4 flex flex-col gap-3">'+
      '<div class="flex items-center justify-between gap-2">'+
        '<div class="flex items-center gap-2 min-w-0">'+
          '<div class="avatar w-8 h-8 bg-slate-800 text-slate-300 text-xs" style="width:32px;height:32px;font-size:11px">'+getInitials(p.nom||p.email)+'</div>'+
          '<div class="min-w-0"><p class="text-sm font-500 text-white truncate">'+(p.nom||p.email)+'</p><p class="text-xs text-slate-500 truncate">'+(p.department||'-')+'</p></div>'+
        '</div>'+getBadge(p.type)+
      '</div>'+
      '<div class="flex items-center justify-between pt-2 border-t border-[#1e2a4a]">'+
        '<div class="text-xs"><span class="text-slate-600">Arrivee</span><br><span class="mono text-emerald-400">'+(p.check_in?p.check_in.slice(0,5):'--')+'</span></div>'+
        '<div class="text-xs text-right"><span class="text-slate-600">Depart</span><br><span class="mono text-blue-400">'+(p.check_out?p.check_out.slice(0,5):'--')+'</span></div>'+
      '</div></div>';
  }).join('');
}
function renderMonthlyChart(){
  var data=[85,78,88,92,80,0,0,0,0,0,0,0];var chart=document.getElementById('monthly-chart');if(!chart)return;
  chart.innerHTML=data.map(function(v){return'<div class="flex-1 flex flex-col items-center"><span class="text-[10px] '+(v?'text-slate-500':'text-slate-700')+' mono mb-1">'+(v?v+'%':'-')+'</span><div class="chart-bar w-full" style="height:'+(v?v:5)+'%;min-height:4px">'+(v?'<div class="bar-fill" style="height:100%"></div>':'')+'</div></div>';}).join('');
}

function setStatus(msg,type){
  var el=document.getElementById('scanner-status');el.textContent=msg;el.className='scanner-status '+type+' mb-4';
}
function demarrerScanner(){
  if(scannerActif) return;
  capturerPosition();
  html5QrCode=new Html5Qrcode("qr-reader");
  html5QrCode.start({facingMode:"environment"},{fps:10,qrbox:{width:250,height:250}},
    function(decodedText){
      var now=Date.now();if(now-lastScanTime<3000)return;lastScanTime=now;
      setStatus('QR detecte ! Traitement...','scanning');envoyerPointage(decodedText);
    },function(){}
  ).then(function(){
    scannerActif=true;
    document.getElementById('btn-start-scan').style.display='none';
    document.getElementById('btn-stop-scan').style.display='block';
    setStatus('Camera active - Approchez le QR Code','scanning');
  }).catch(function(err){setStatus('Impossible d\'acceder a la camera','error');showToast('Erreur camera: '+err,'error');});
}
function arreterScanner(){
  if(!html5QrCode||!scannerActif)return;
  html5QrCode.stop().then(function(){
    scannerActif=false;
    document.getElementById('btn-start-scan').style.display='block';
    document.getElementById('btn-stop-scan').style.display='none';
    document.getElementById('qr-reader').innerHTML='';
    setStatus('Scanner arrete','idle');
  }).catch(function(err){console.log(err);});
}
function pointerManuellement(){
  var token=document.getElementById('manual-token').value.trim();
  if(!token){showToast('Entrez un token','error');return;}
  envoyerPointage(token);document.getElementById('manual-token').value='';
}
function envoyerPointage(token){
  var body='token='+encodeURIComponent(token);
  if(geoPosition){body+='&latitude='+encodeURIComponent(geoPosition.lat)+'&longitude='+encodeURIComponent(geoPosition.lng);}
  fetch('index.php?route=presences/pointer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body})
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.success){
      var libelle=res.data.mode==='depart'?'Depart':'Arrivee';
      setStatus(libelle+' enregistree pour '+res.data.etudiant,'success');
      showToast(libelle+' : '+res.data.etudiant,'success');
      ajouterScanLog(res.data.etudiant,res.data.heure,libelle,res.data.type||'present');
      setTimeout(function(){setStatus('Camera active - Approchez le QR Code','scanning');},2000);
    } else {
      setStatus('Erreur: '+res.message,'error');showToast(res.message,'error');
      setTimeout(function(){setStatus('Camera active - Approchez le QR Code','scanning');},2000);
    }
  }).catch(function(){showToast('Erreur reseau','error');});
}
function ajouterScanLog(nom,heure,type,statut){
  var log=document.getElementById('scan-log');
  var placeholder=log.querySelector('div.text-center');if(placeholder)placeholder.remove();
  scanCount++;document.getElementById('scan-count').textContent=scanCount+' aujourd\'hui';
  var item=document.createElement('div');item.className='flex items-center justify-between p-3 bg-[#0a0e1a] rounded-xl';
  item.innerHTML='<div class="flex items-center gap-3"><div class="avatar w-9 h-9 bg-slate-800 text-slate-300 text-xs">'+getInitials(nom)+'</div><div><p class="text-sm font-500 text-white">'+nom+'</p><p class="text-xs text-slate-500">'+type+(geoPosition?' &bull; <span style="color:#34d399">GPS ✓</span>':' &bull; <span style="color:#475569">GPS —</span>')+'</p></div></div><div class="text-right"><p class="mono text-sm text-emerald-400">'+(heure?heure.slice(0,5):'')+'</p>'+getBadge(statut)+'</div>';
  log.prepend(item);
}

function actualiserStats(){
  fetch('index.php?route=dashboard/stats').then(function(r){return r.json();}).then(function(res){
    if(!res.success)return;
    document.getElementById('stat-present').textContent=res.presents;
    document.getElementById('stat-absent').textContent=res.absents;
    document.getElementById('stat-retard').textContent=res.retards;
    document.getElementById('stat-total').textContent=res.totalEtudiants;
    document.getElementById('total-display').textContent=res.totalEtudiants;
    var total=res.presents+res.absents+res.retards;
    var tp=total>0?Math.round(res.presents/total*100):0;
    var ta=total>0?Math.round(res.absents/total*100):0;
    var tr=total>0?Math.round(res.retards/total*100):0;
    document.getElementById('taux-container').innerHTML=
      '<div><div class="flex justify-between text-sm mb-1"><span class="text-slate-400">Presents</span><span class="text-emerald-400 font-600">'+tp+'%</span></div><div class="progress-bar"><div class="progress-fill bg-emerald-500" style="width:'+tp+'%"></div></div></div>'+
      '<div><div class="flex justify-between text-sm mb-1"><span class="text-slate-400">Absents</span><span class="text-red-400 font-600">'+ta+'%</span></div><div class="progress-bar"><div class="progress-fill bg-red-500" style="width:'+ta+'%"></div></div></div>'+
      '<div><div class="flex justify-between text-sm mb-1"><span class="text-slate-400">Retards</span><span class="text-yellow-400 font-600">'+tr+'%</span></div><div class="progress-bar"><div class="progress-fill bg-yellow-500" style="width:'+tr+'%"></div></div></div>';
  }).catch(function(){showToast('Erreur actualisation','error');});
}

// ============================================================
// PRESENCES
// ============================================================
var presencesCurrentPage=1;var presencesLimit=10;var presencesAllData=[];
function chargerPresences(){presencesCurrentPage=1;_fetchPresences();}
function presencesPage(dir){presencesCurrentPage+=dir;if(presencesCurrentPage<1)presencesCurrentPage=1;_renderPresences();}
function _fetchPresences(){
  var date=document.getElementById('filter-date')?document.getElementById('filter-date').value:'';
  var dept=document.getElementById('filter-dept')?document.getElementById('filter-dept').value:'';
  var statut=document.getElementById('filter-statut')?document.getElementById('filter-statut').value:'';
  var url='index.php?route=presences/liste&date='+encodeURIComponent(date)+'&dept='+encodeURIComponent(dept)+'&statut='+encodeURIComponent(statut);
  document.getElementById('presences-tbody').innerHTML='<p class="text-slate-500 text-sm text-center py-8 col-span-full">Chargement...</p>';
  fetch(url).then(function(r){return r.json();}).then(function(res){
    if(!res.success||res.data.length===0){presencesAllData=[];_renderPresences();return;}
    presencesAllData=res.data;_renderPresences();
  }).catch(function(){showToast('Erreur chargement presences','error');});
}
function _renderPresences(){
  var grid=document.getElementById('presences-tbody');
  var total=presencesAllData.length;
  if(total===0){
    grid.innerHTML='<p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucune presence pour cette date</p>';
    document.getElementById('presences-page-info').textContent='';
    togglePagination('presences-pagination', false);
    return;
  }
  var totalPages=Math.ceil(total/presencesLimit);
  if(presencesCurrentPage>totalPages)presencesCurrentPage=totalPages;
  var start=(presencesCurrentPage-1)*presencesLimit;
  var page=presencesAllData.slice(start,start+presencesLimit);
  // Pagination : visible seulement si plus d'une page
  togglePagination('presences-pagination', totalPages > 1);
  document.getElementById('presences-page-info').textContent='Page '+presencesCurrentPage+' / '+totalPages+' ('+total+')';
  document.getElementById('presences-prev').disabled=presencesCurrentPage<=1;
  document.getElementById('presences-next').disabled=presencesCurrentPage>=totalPages;
  grid.innerHTML=page.map(function(p){
    var duree='--';
    if(p.check_in&&p.check_out){var ci=p.check_in.split(':');var co=p.check_out.split(':');var mins=(parseInt(co[0])*60+parseInt(co[1]))-(parseInt(ci[0])*60+parseInt(ci[1]));duree=Math.floor(mins/60)+'h'+String(mins%60).padStart(2,'0');}
    return '<div class="card p-4 flex flex-col gap-3">'+
      '<div class="flex items-center justify-between gap-2">'+
        '<div class="flex items-center gap-2 min-w-0">'+
          '<div class="avatar w-8 h-8 bg-slate-800 text-slate-300 text-xs" style="width:32px;height:32px;font-size:11px">'+getInitials(p.nom||p.email)+'</div>'+
          '<div class="min-w-0"><p class="text-sm font-500 text-white truncate">'+(p.nom||p.email)+'</p><p class="text-xs text-slate-500 truncate">'+(p.department||'-')+'</p></div>'+
        '</div>'+getBadge(p.type)+
      '</div>'+
      '<div class="flex items-center justify-between text-xs"><span class="mono text-slate-500">'+p.date+'</span><span class="mono text-slate-400">'+duree+'</span></div>'+
      '<div class="flex items-center justify-between pt-2 border-t border-[#1e2a4a]">'+
        '<div class="text-xs"><span class="text-slate-600">Arrivee</span><br><span class="mono text-emerald-400">'+(p.check_in?p.check_in.slice(0,5):'--')+'</span></div>'+
        '<div class="text-xs text-right"><span class="text-slate-600">Depart</span><br><span class="mono text-blue-400">'+(p.check_out?p.check_out.slice(0,5):'--')+'</span></div>'+
      '</div></div>';
  }).join('');
}

// ============================================================
// ETUDIANTS
// ============================================================
function chargerEtudiants(){
  var search=document.getElementById('search-etu')?document.getElementById('search-etu').value:'';
  fetch('index.php?route=etudiants/liste&search='+encodeURIComponent(search))
  .then(function(r){return r.json();}).then(function(res){
    var grid=document.getElementById('etudiants-grid');
    if(!res.success||res.data.length===0){grid.innerHTML='<p class="text-slate-500 text-sm col-span-3 text-center py-8">Aucun etudiant</p>';return;}
    grid.innerHTML=res.data.map(function(e){
      return '<div class="card p-5"><div class="flex items-start justify-between mb-3"><div class="flex items-center gap-3"><div class="avatar w-10 h-10 bg-emerald-900 text-emerald-300" style="width:40px;height:40px;font-size:13px">'+getInitials(e.nom)+'</div><div><p class="font-600 text-white text-sm">'+e.nom+'</p><p class="text-xs text-slate-400">'+e.department+'</p></div></div><span class="text-xs badge-present px-2 py-0.5 rounded-full">Etudiant</span></div><div class="text-xs text-slate-400 border-t border-[#1e2a4a] pt-3 space-y-1"><p>'+e.email+'</p><p class="mono text-emerald-600 text-[10px]">'+(e.qr_token?'QR: '+e.qr_token.slice(0,16)+'...':'Pas de QR')+'</p></div><div class="flex gap-2 mt-3"><button class="btn-secondary text-xs px-2 py-1.5" onclick="voirQR(\''+e.id+'\',\''+e.nom+'\',\''+(e.qr_token||'')+'\')">QR</button><button class="btn-primary text-xs px-2 py-1.5 flex-1" onclick="modifierEtudiant(\''+e.id+'\',\''+e.nom+'\',\''+e.email+'\',\''+e.department+'\')">Modifier</button><button class="btn-danger text-xs px-2 py-1.5" onclick="desactiverEtudiant(\''+e.id+'\',\''+e.nom+'\')">X</button></div></div>';
    }).join('');
  }).catch(function(){showToast('Erreur chargement','error');});
}
function creerEtudiant(){
  var nom=document.getElementById('etu-nom').value.trim();var email=document.getElementById('etu-email').value.trim();var dept=document.getElementById('etu-dept').value;
  if(!nom||!email||!dept){showToast('Tous les champs sont requis','error');return;}
  fetch('index.php?route=etudiants/creer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'nom='+encodeURIComponent(nom)+'&email='+encodeURIComponent(email)+'&department='+encodeURIComponent(dept)})
  .then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success){closeModal();chargerEtudiants();}}).catch(function(){showToast('Erreur reseau','error');});
}
function modifierEtudiant(id,nom,email,dept){
  document.getElementById('modal-bg').classList.remove('hidden');
  document.getElementById('modal-content').innerHTML='<h2 class="text-lg font-700 text-white mb-5">Modifier Etudiant</h2><div class="space-y-4"><div><label class="text-xs text-slate-500 block mb-1">Nom complet</label><input type="text" id="edit-nom" value="'+nom+'"></div><div><label class="text-xs text-slate-500 block mb-1">Email</label><input type="email" id="edit-email" value="'+email+'"></div><div><label class="text-xs text-slate-500 block mb-1">Departement</label><select id="edit-dept">'+deptOptions(dept)+'</select></div><div><label class="text-xs text-slate-500 block mb-1">Nouveau mot de passe <span class="text-slate-600">(laisser vide)</span></label><input type="password" id="edit-password" placeholder="..."></div></div><div class="flex gap-3 mt-6"><button class="btn-primary flex-1 py-2.5" onclick="sauvegarderEtudiant('+id+')">Sauvegarder</button><button class="btn-secondary" onclick="closeModal()">Annuler</button></div>';
}
function sauvegarderEtudiant(id){
  var nom=document.getElementById('edit-nom').value.trim();var email=document.getElementById('edit-email').value.trim();var dept=document.getElementById('edit-dept').value;var password=document.getElementById('edit-password').value;
  if(!nom||!email){showToast('Nom et email requis','error');return;}
  fetch('index.php?route=etudiants/modifier',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id)+'&nom='+encodeURIComponent(nom)+'&email='+encodeURIComponent(email)+'&department='+encodeURIComponent(dept)+'&password='+encodeURIComponent(password)})
  .then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success){closeModal();chargerEtudiants();}}).catch(function(){showToast('Erreur reseau','error');});
}
function desactiverEtudiant(id,nom){
  if(!confirm('Desactiver '+nom+' ?'))return;
  fetch('index.php?route=etudiants/supprimer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id)})
  .then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success)chargerEtudiants();});
}
function voirQR(id,nom,token){
  document.getElementById('modal-bg').classList.remove('hidden');
  var btnCopier=token?'<button class="btn-primary flex-1 py-2.5" onclick="copierToken(this.dataset.token)" data-token="'+token+'">Copier le token</button>':'';
  document.getElementById('modal-content').innerHTML='<h2 class="text-lg font-700 text-white mb-2">QR Code &mdash; '+nom+'</h2><p class="text-slate-400 text-sm mb-4">Scannez ce QR Code pour pointer</p><div class="bg-[#0a0e1a] rounded-xl p-6 text-center border border-[#1e2a4a] mb-4"><div id="qrcode-display" class="inline-block p-3 bg-white rounded-xl mb-3"></div><p class="mono text-xs text-slate-300 break-all px-2 mt-2">'+(token||'Aucun token')+'</p></div><div class="flex gap-3">'+btnCopier+'<button class="btn-secondary py-2.5 flex-1" onclick="closeModal()">Fermer</button></div>';
  setTimeout(function(){var container=document.getElementById('qrcode-display');if(container&&token){new QRCode(container,{text:token,width:180,height:180,colorDark:'#000000',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.H});}},100);
}
function copierToken(el){var token=typeof el==='string'?el:el.dataset.token;navigator.clipboard.writeText(token).then(function(){showToast('Token copie !','success');}).catch(function(){showToast('Erreur copie','error');});}

// ============================================================
// CONGES / ABSENCES
// ============================================================
var congesCurrentPage=1;var congesLimit=10;var congesAllData=[];
function chargerConges(){congesCurrentPage=1;_fetchConges();}
function congesPage(dir){congesCurrentPage+=dir;if(congesCurrentPage<1)congesCurrentPage=1;_renderConges();}
function _fetchConges(){
  var statut=document.getElementById('conge-filter-statut')?document.getElementById('conge-filter-statut').value:'';
  fetch('index.php?route=conges/liste&statut='+encodeURIComponent(statut)).then(function(r){return r.json();}).then(function(res){
    if(!res.success||res.data.length===0){
      document.getElementById('conges-tbody').innerHTML='<p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucune absence enregistree</p>';
      updateCongeStats([]);
      togglePagination('conges-pagination', false);
      return;
    }
    congesAllData=res.data;updateCongeStats(res.data);_renderConges();
  }).catch(function(){showToast('Erreur chargement absences','error');});
}
function _renderConges(){
  var grid=document.getElementById('conges-tbody');var total=congesAllData.length;
  if(total===0){
    grid.innerHTML='<p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucune absence enregistree</p>';
    document.getElementById('conges-page-info').textContent='';
    togglePagination('conges-pagination', false);
    return;
  }
  var totalPages=Math.ceil(total/congesLimit);if(congesCurrentPage>totalPages)congesCurrentPage=totalPages;
  var start=(congesCurrentPage-1)*congesLimit;var page=congesAllData.slice(start,start+congesLimit);
  togglePagination('conges-pagination', totalPages > 1);
  document.getElementById('conges-page-info').textContent='Page '+congesCurrentPage+' / '+totalPages+' ('+total+')';
  document.getElementById('conges-prev').disabled=congesCurrentPage<=1;
  document.getElementById('conges-next').disabled=congesCurrentPage>=totalPages;
  grid.innerHTML=page.map(function(c){
    var typeLabel={maladie:'Maladie',conge_annuel:'Conge',urgence:'Urgence',autre:'Autre',absence_passee:'Abs. passee',absence_future:'Abs. future',rendez_vous_medical:'RDV med.',probleme_transport:'Transport'};
    var actions=c.status==='en_attente'
      ?'<button class="btn-success text-xs px-3 py-2 flex-1" onclick="approuverConge('+c.id+')">Approuver</button><button class="btn-danger text-xs px-3 py-2 flex-1" onclick="refuserConge('+c.id+')">Refuser</button>'
      :'<button class="btn-danger text-xs px-3 py-2 w-full" onclick="supprimerConge('+c.id+')">Supprimer</button>';
    var today=new Date().toISOString().split('T')[0];var dateAbs=c.date_absence||c.start_date;
    var absTag=dateAbs?(dateAbs<=today?'<span class="text-[10px] badge-absent px-1.5 py-0.5 rounded ml-1">Passee</span>':'<span class="text-[10px] badge-pending px-1.5 py-0.5 rounded ml-1">Future</span>'):'';
    var docLink=c.document?'<a href="uploads/justificatifs/'+c.document+'" target="_blank" class="text-xs text-emerald-400 underline">Voir le justificatif</a>':'<span class="text-xs text-slate-600">Pas de justificatif</span>';
    return '<div class="card p-4 flex flex-col gap-3">'+
      '<div class="flex items-center justify-between gap-2">'+
        '<div class="flex items-center gap-2 min-w-0">'+
          '<div class="avatar w-8 h-8 bg-slate-800 text-slate-300 text-xs" style="width:32px;height:32px;font-size:11px">'+getInitials(c.nom)+'</div>'+
          '<div class="min-w-0"><p class="text-sm font-500 text-white truncate">'+c.nom+'</p><p class="text-xs text-slate-500 truncate">'+(c.department||'-')+'</p></div>'+
        '</div>'+getBadgeConge(c.status)+
      '</div>'+
      '<div class="flex items-center justify-between text-xs"><span class="text-slate-400">'+(typeLabel[c.type]||c.type)+absTag+'</span><span class="mono text-slate-500">'+dateAbs+'</span></div>'+
      (c.reason?'<p class="text-xs text-slate-400 line-clamp-2">'+c.reason+'</p>':'')+
      '<div class="flex items-center justify-between pt-2 border-t border-[#1e2a4a]">'+docLink+'</div>'+
      '<div class="flex gap-2">'+actions+'</div></div>';
  }).join('');
}
function updateCongeStats(data){
  var attente=data.filter(function(c){return c.status==='en_attente';}).length;
  var approuve=data.filter(function(c){return c.status==='approuve';}).length;
  var refuse=data.filter(function(c){return c.status==='refuse';}).length;
  var e1=document.getElementById('conge-count-attente');var e2=document.getElementById('conge-count-approuve');var e3=document.getElementById('conge-count-refuse');
  if(e1)e1.textContent=attente;if(e2)e2.textContent=approuve;if(e3)e3.textContent=refuse;
}
function getBadgeConge(status){var map={en_attente:['badge-pending','Attente'],approuve:['badge-present','OK'],refuse:['badge-absent','Refuse']};var item=map[status]||['badge-pending',status];return '<span class="text-xs '+item[0]+' px-2 py-0.5 rounded-full whitespace-nowrap">'+item[1]+'</span>';}
function approuverConge(id){fetch('index.php?route=conges/approuver',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success)chargerConges();});}
function refuserConge(id){fetch('index.php?route=conges/refuser',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success)chargerConges();});}
function supprimerConge(id){if(!confirm('Supprimer cette demande ?'))return;fetch('index.php?route=conges/supprimer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success)chargerConges();});}
function ouvrirModalConge(){
  document.getElementById('modal-bg').classList.remove('hidden');
  document.getElementById('modal-content').innerHTML='<h2 class="text-lg font-700 text-white mb-5">Nouvelle Absence</h2><div class="space-y-4"><div><label class="text-xs text-slate-500 block mb-1">Etudiant</label><select id="conge-user-id" class="w-full"></select></div><div><label class="text-xs text-slate-500 block mb-1">Type</label><select id="conge-type"><option value="maladie">Maladie</option><option value="conge_annuel">Conge annuel</option><option value="urgence">Urgence</option><option value="autre">Autre</option></select></div><div class="grid grid-cols-2 gap-3"><div><label class="text-xs text-slate-500 block mb-1">Date debut</label><input type="date" id="conge-start"></div><div><label class="text-xs text-slate-500 block mb-1">Date fin</label><input type="date" id="conge-end"></div></div><div><label class="text-xs text-slate-500 block mb-1">Motif</label><textarea id="conge-reason" rows="3" style="resize:none"></textarea></div></div><div class="flex gap-3 mt-6"><button class="btn-primary flex-1 py-2.5" onclick="soumettreConge()">Soumettre</button><button class="btn-secondary" onclick="closeModal()">Annuler</button></div>';
  fetch('index.php?route=etudiants/liste').then(function(r){return r.json();}).then(function(res){var sel=document.getElementById('conge-user-id');if(sel&&res.data){sel.innerHTML=res.data.map(function(e){return'<option value="'+e.id+'">'+e.nom+' ('+e.department+')</option>';}).join('');}});
}
function soumettreConge(){
  var userId=document.getElementById('conge-user-id')?document.getElementById('conge-user-id').value:'';
  var type=document.getElementById('conge-type').value;var startDate=document.getElementById('conge-start').value;var endDate=document.getElementById('conge-end').value;var reason=document.getElementById('conge-reason').value;
  if(!startDate||!endDate){showToast('Dates requises','error');return;}
  fetch('index.php?route=conges/soumettre',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'user_id='+encodeURIComponent(userId)+'&type='+encodeURIComponent(type)+'&start_date='+encodeURIComponent(startDate)+'&end_date='+encodeURIComponent(endDate)+'&reason='+encodeURIComponent(reason)})
  .then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success){closeModal();chargerConges();}}).catch(function(){showToast('Erreur reseau','error');});
}

// ============================================================
// AUDIT LOG
// ============================================================
var auditCurrentPage=1;
var auditAutoRefresh=null;
function getAuditLabel(action){var labels={login:{text:'LOGIN',cls:'badge-present'},logout:{text:'LOGOUT',cls:'badge-pending'},checkin:{text:'ARRIVEE',cls:'badge-present'},checkout:{text:'DEPART',cls:'badge-retard'},create:{text:'CREATION',cls:'badge-present'},update:{text:'MODIF',cls:'badge-retard'},delete:{text:'SUPPRIME',cls:'badge-absent'},approve:{text:'APPROUVE',cls:'badge-present'},refuse:{text:'REFUSE',cls:'badge-absent'},conge_soumettre:{text:'ABSENCE',cls:'badge-pending'},conge_approuve:{text:'ABS. OK',cls:'badge-present'},conge_refuse:{text:'ABS. KO',cls:'badge-absent'},conge_supprime:{text:'ABS. SUPP',cls:'badge-absent'},justificatif:{text:'JUSTIF',cls:'badge-retard'},update_gps:{text:'GPS',cls:'badge-pending'}};return labels[action]||{text:(action||'--').toUpperCase(),cls:'badge-pending'};}
function getAuditDetail(log){var action=log.action;var nom=log.nom||log.email||'Systeme';var detail='';if(action==='checkin')detail='Arrivee de '+log.entity+' pointee par '+nom;else if(action==='checkout')detail='Depart de '+log.entity+' pointe par '+nom;else if(action==='login')detail='Connexion de '+nom;else if(action==='logout')detail='Deconnexion de '+nom;else if(action==='create')detail='Etudiant cree par '+nom;else if(action==='update')detail='Etudiant modifie par '+nom;else if(action==='delete')detail='Etudiant desactive par '+nom;else if(action==='approve')detail='Compte approuve par '+nom;else if(action==='refuse')detail='Compte refuse par '+nom;else if(action==='conge_soumettre')detail='Demande absence pour '+log.entity+' par '+nom;else if(action==='conge_approuve')detail='Absence de '+log.entity+' approuvee par '+nom;else if(action==='conge_refuse')detail='Absence de '+log.entity+' refusee par '+nom;else if(action==='conge_supprime')detail='Demande absence supprimee par '+nom;else if(action==='justificatif')detail='Justificatif genere pour '+log.entity+' par '+nom;else if(action==='update_gps')detail='Position GPS de l\'ecole modifiee par '+nom;else detail=nom;var entity=log.entity?log.entity+(log.entity_id?' #'+log.entity_id:''):'';return{main:nom,sub:action==='update_gps'?detail:(entity||detail)};}
function chargerAudit(){
  var search=document.getElementById('audit-search')?document.getElementById('audit-search').value:'';
  var action=document.getElementById('audit-filter-action')?document.getElementById('audit-filter-action').value:'';
  fetch('index.php?route=audit/liste&page='+auditCurrentPage+'&search='+encodeURIComponent(search)+'&action='+encodeURIComponent(action)).then(function(r){return r.json();}).then(function(res){
    var list=document.getElementById('audit-list');
    if(!res.success||res.data.length===0){
      list.innerHTML='<p class="text-slate-500 text-sm text-center py-8">Aucune entree dans l\'audit log</p>';
      document.getElementById('audit-total').textContent='0 entrees';
      togglePagination('audit-pagination', false);
      return;
    }
    var sel=document.getElementById('audit-filter-action');
    if(sel&&res.actions){var cur=sel.value;sel.innerHTML='<option value="">Toutes les actions</option>'+res.actions.map(function(a){return'<option value="'+a+'"'+(a===cur?' selected':'')+'>'+a+'</option>';}).join('');}
    document.getElementById('audit-total').textContent=res.total+' entrees';
    document.getElementById('audit-page-info').textContent='Page '+res.page+' / '+res.pages;
    document.getElementById('audit-prev').disabled=res.page<=1;
    document.getElementById('audit-next').disabled=res.page>=res.pages;
    // Pagination visible seulement si plusieurs pages
    togglePagination('audit-pagination', res.pages > 1);
    list.innerHTML=res.data.map(function(l){
      var label=getAuditLabel(l.action);var detail=getAuditDetail(l);var heure=l.created_at?l.created_at.slice(11,19):'--:--:--';
      return '<div class="card p-4 flex flex-col gap-3">'+
        '<div class="flex items-center justify-between gap-2"><span class="text-xs '+label.cls+' px-2.5 py-1 rounded-full font-700">'+label.text+'</span><span class="mono text-xs text-slate-500">'+heure+'</span></div>'+
        '<div><div class="flex items-center gap-2"><p class="text-sm font-500 text-white truncate">'+detail.main+'</p><span class="text-[10px] badge-pending px-1.5 py-0.5 rounded-full uppercase">'+(l.role||'')+'</span></div><p class="text-xs text-slate-500 truncate">'+detail.sub+'</p></div>'+
        '<div class="flex items-center justify-between pt-2 border-t border-[#1e2a4a] text-xs"><span class="text-slate-600">Adresse IP</span><span class="mono text-slate-500">'+(l.ip||'--')+'</span></div></div>';
    }).join('');
  }).catch(function(){showToast('Erreur audit','error');});
}
function auditPage(dir){auditCurrentPage+=dir;if(auditCurrentPage<1)auditCurrentPage=1;chargerAudit();}

// ============================================================
// VALIDATION
// ============================================================
function chargerValidation(){
  fetch('index.php?route=validation/liste').then(function(r){return r.json();}).then(function(res){
    var list=document.getElementById('validation-list');if(!list)return;
    if(!res.success||res.data.length===0){
      list.innerHTML='<p class="text-slate-500 text-sm text-center py-8 col-span-full">Aucune invitation en attente</p>';
      document.getElementById('valid-page-info').textContent='';
      togglePagination('valid-pagination', false);
      return;
    }
    var limit=10;var total=res.data.length;var totalPages=Math.ceil(total/limit);
    if(!window.validPage)window.validPage=1;
    var start=(window.validPage-1)*limit;var page=res.data.slice(start,start+limit);
    togglePagination('valid-pagination', totalPages > 1);
    document.getElementById('valid-page-info').textContent='Page '+window.validPage+' / '+totalPages+' ('+total+')';
    document.getElementById('valid-prev').disabled=window.validPage<=1;
    document.getElementById('valid-next').disabled=window.validPage>=totalPages;
    list.innerHTML=page.map(function(c){
      return '<div class="card p-4 flex flex-col gap-3">'+
        '<div class="flex items-center gap-3">'+
          '<div class="avatar w-10 h-10 bg-blue-900 text-blue-300 text-sm" style="width:40px;height:40px;font-size:13px">'+getInitials(c.nom)+'</div>'+
          '<div class="min-w-0 flex-1"><p class="font-500 text-white text-sm truncate">'+c.nom+'</p><p class="text-xs text-slate-400 truncate">'+c.email+'</p></div>'+
          '<span class="text-xs badge-pending px-2 py-0.5 rounded-full flex-shrink-0">En attente</span>'+
        '</div>'+
        '<div class="flex items-center justify-between text-xs"><span class="text-slate-500">'+c.department+'</span><span class="mono text-slate-600">'+c.created_at+'</span></div>'+
        '<div class="flex gap-2 pt-2 border-t border-[#1e2a4a]">'+
          '<button class="btn-secondary text-xs px-3 py-2 flex-1" onclick="renvoyerInvitation(\''+c.id+'\',\''+c.nom+'\')">Renvoyer</button>'+
          '<button class="btn-danger text-xs px-3 py-2 flex-1" onclick="refuserCompte(\''+c.id+'\',\''+c.nom+'\')">Annuler</button>'+
        '</div></div>';
    }).join('');
  }).catch(function(){showToast('Erreur chargement','error');});
}
function validationPage(dir){if(!window.validPage)window.validPage=1;window.validPage+=dir;if(window.validPage<1)window.validPage=1;chargerValidation();}
function renvoyerInvitation(id,nom){
  fetch('index.php?route=validation/renvoyer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id)})
  .then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');});
}
function refuserCompte(id,nom){if(!confirm('Annuler l\'invitation de '+nom+' ?'))return;fetch('index.php?route=validation/refuser',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id)}).then(function(r){return r.json();}).then(function(res){showToast(res.success?'Invitation de '+nom+' annulee':res.message,res.success?'success':'error');if(res.success)chargerValidation();});}

// ============================================================
// MODALS
// ============================================================
function openModal(name){
  document.getElementById('modal-bg').classList.remove('hidden');
  document.getElementById('modal-content').innerHTML='<h2 class="text-lg font-700 text-white mb-2">Ajouter un Etudiant</h2><p class="text-xs text-slate-500 mb-5">Un email d\'invitation lui sera envoye pour qu\'il/elle choisisse son propre mot de passe.</p><div class="space-y-4"><div><label class="text-xs text-slate-500 block mb-1">Nom complet</label><input type="text" id="etu-nom" placeholder="Moussa Diallo"></div><div><label class="text-xs text-slate-500 block mb-1">Email</label><input type="email" id="etu-email" placeholder="etudiant@ecole.sn"></div><div><label class="text-xs text-slate-500 block mb-1">Departement</label><select id="etu-dept">'+deptOptions('')+'</select></div></div><div class="flex gap-3 mt-6"><button class="btn-primary flex-1 py-2.5" onclick="creerEtudiant()">Envoyer l\'invitation</button><button class="btn-secondary" onclick="closeModal()">Annuler</button></div>';
}
function closeModal(){document.getElementById('modal-bg').classList.add('hidden');}
function openModalImport(){
  document.getElementById('modal-bg').classList.remove('hidden');
  document.getElementById('modal-content').innerHTML=
    '<h2 class="text-lg font-700 text-white mb-2">Importer des etudiants</h2>'+
    '<p class="text-xs text-slate-500 mb-1">Chaque etudiant valide recevra un email d\'invitation pour activer son compte.</p>'+
    '<a href="index.php?route=etudiants/modele" class="text-xs text-emerald-400 underline inline-block mb-4">Telecharger le modele Excel</a>'+
    '<div><label class="text-xs text-slate-500 block mb-1">Fichier Excel (.xlsx)</label><input type="file" id="import-fichier" accept=".xlsx,.xls"></div>'+
    '<div id="import-resultat" class="mt-3"></div>'+
    '<div class="flex gap-3 mt-6"><button class="btn-primary flex-1 py-2.5" id="btn-import" onclick="importerEtudiants()">Importer</button><button class="btn-secondary" onclick="closeModal()">Fermer</button></div>';
}
function importerEtudiants(){
  var input=document.getElementById('import-fichier');
  if(!input.files||!input.files[0]){showToast('Selectionnez un fichier Excel','error');return;}
  var btn=document.getElementById('btn-import');btn.disabled=true;btn.textContent='Import en cours...';
  var formData=new FormData();formData.append('fichier', input.files[0]);
  fetch('index.php?route=etudiants/importer',{method:'POST',body:formData})
  .then(function(r){return r.json();})
  .then(function(res){
    btn.disabled=false;btn.textContent='Importer';
    var zone=document.getElementById('import-resultat');
    var html='<div class="text-xs '+(res.success?'text-emerald-400':'text-red-400')+' font-600 mb-2">'+res.message+'</div>';
    if(res.data&&res.data.reussis&&res.data.reussis.length){html+='<div class="bg-[#0a0e1a] rounded-lg p-3 mb-2 max-h-32 overflow-y-auto"><p class="text-xs text-emerald-400 font-600 mb-1">Importes :</p>'+res.data.reussis.map(function(r){return '<p class="text-xs text-slate-400">'+r+'</p>';}).join('')+'</div>';}
    if(res.data&&res.data.erreurs&&res.data.erreurs.length){html+='<div class="bg-[#0a0e1a] rounded-lg p-3 max-h-32 overflow-y-auto"><p class="text-xs text-red-400 font-600 mb-1">Erreurs :</p>'+res.data.erreurs.map(function(e){return '<p class="text-xs text-slate-400">'+e+'</p>';}).join('')+'</div>';}
    zone.innerHTML=html;showToast(res.message,res.success?'success':'error');chargerEtudiants();
  }).catch(function(){btn.disabled=false;btn.textContent='Importer';showToast('Erreur reseau','error');});
}

// ============================================================
// PROFIL
// ============================================================
function sauvegarderProfil(){
  var nom=document.getElementById('profil-nom').value.trim();
  var email=document.getElementById('profil-email').value.trim();
  var password=document.getElementById('profil-password').value;
  if(!nom||!email){showToast('Nom et email requis','error');return;}
  fetch('index.php?route=profil/modifier',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'nom='+encodeURIComponent(nom)+'&email='+encodeURIComponent(email)+'&password='+encodeURIComponent(password)})
  .then(function(r){return r.json();}).then(function(res){
    showToast(res.message,res.success?'success':'error');
    if(res.success)document.getElementById('profil-password').value='';
  }).catch(function(){showToast('Erreur reseau','error');});
}

// ============================================================
// QR CODE ECOLE
// ============================================================
var schoolQrInstance=null;
function chargerQrEcole(){
  fetch('index.php?route=school/qr').then(function(r){return r.json();}).then(function(res){
    if(!res.success){showToast('Erreur chargement QR ecole','error');return;}
    var data=res.data;
    document.getElementById('school-qr-token').textContent=data.token;
    document.getElementById('school-qr-label').textContent=data.label||'Entree principale';
    var urlEl=document.getElementById('school-qr-url');if(urlEl) urlEl.textContent=data.url||'';
    var container=document.getElementById('school-qr-container');container.innerHTML='';
    schoolQrInstance=new QRCode(container,{text:data.url||data.token,width:150,height:150,colorDark:'#000000',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.H});
  }).catch(function(){showToast('Erreur reseau','error');});
  fetch('index.php?route=school/config').then(function(r){return r.json();}).then(function(res){
    if(res.success&&res.data){
      document.getElementById('config-latitude').value=res.data.latitude;
      document.getElementById('config-longitude').value=res.data.longitude;
      document.getElementById('config-rayon').value=res.data.rayon;
    }
  }).catch(function(){});
}
function enregistrerConfigGps(){
  var lat=document.getElementById('config-latitude').value.trim();
  var lng=document.getElementById('config-longitude').value.trim();
  var rayon=document.getElementById('config-rayon').value.trim();
  if(!lat||!lng||!rayon){showToast('Tous les champs sont requis','error');return;}
  fetch('index.php?route=school/config/update',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'latitude='+encodeURIComponent(lat)+'&longitude='+encodeURIComponent(lng)+'&rayon='+encodeURIComponent(rayon)})
  .then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');}).catch(function(){showToast('Erreur reseau','error');});
}
function imprimerQrEcole(){
  var container=document.getElementById('school-qr-container');
  var token=document.getElementById('school-qr-token').textContent;
  var label=document.getElementById('school-qr-label').textContent;
  var urlAffichee=document.getElementById('school-qr-url').textContent||token;
  var canvas=container.querySelector('canvas');var imgSrc=canvas?canvas.toDataURL('image/png'):'';
  var win=window.open('','_blank');
  win.document.write('<html><body style="text-align:center;font-family:Arial;padding:40px"><h2 style="color:#059669">PointagePro</h2><p style="color:#666;font-size:14px">'+label+'</p>'+(imgSrc?'<img src="'+imgSrc+'" style="width:280px;height:280px;margin:20px 0">':'')+'<p style="font-family:monospace;font-size:10px;color:#999;word-break:break-all;max-width:320px;margin:0 auto">'+urlAffichee+'</p><p style="color:#666;font-size:12px;margin-top:20px">Scannez ce QR Code pour pointer votre presence</p></body></html>');
  win.document.close();win.print();
}
function regenererQrEcole(){
  if(!confirm('Regenerer le QR Code ? L\'ancien ne fonctionnera plus !'))return;
  fetch('index.php?route=school/qr/regenerer',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'label=Entree principale'})
  .then(function(r){return r.json();}).then(function(res){showToast(res.message,res.success?'success':'error');if(res.success)chargerQrEcole();});
}

// ============================================================
// RAPPORTS
// ============================================================
function exporterRapport(type){
  var mois=document.getElementById('rapport-mois')?document.getElementById('rapport-mois').value:'';
  if(!mois){showToast('Selectionnez un mois','error');return;}
  var url='';
  if(type==='mensuel'){url='index.php?route=rapport/mensuel/pdf&mois='+mois;showToast('Generation du rapport PDF...','info');}
  else if(type==='excel'){var dept=document.getElementById('rapport-dept')?document.getElementById('rapport-dept').value:'';url='index.php?route=rapport/presences/excel&mois='+mois+'&dept='+encodeURIComponent(dept);showToast('Generation du fichier Excel...','info');}
  else if(type==='conges'){url='index.php?route=rapport/conges/pdf&mois='+mois;showToast('Generation du rapport conges PDF...','info');}
  var a=document.createElement('a');a.href=url;a.download='';document.body.appendChild(a);a.click();document.body.removeChild(a);
}

// ============================================================
// TOAST
// ============================================================
var toastTimeout;
function showToast(msg,type){
  type=type||'info';var ex=document.querySelector('.toast');if(ex)ex.remove();clearTimeout(toastTimeout);
  var colors={success:'#34d399',error:'#f87171',info:'#60a5fa',warning:'#fbbf24'};
  var icons={success:'&#10003;',error:'&#10005;',info:'&#9432;',warning:'&#9888;'};
  var t=document.createElement('div');t.className='toast';
  t.innerHTML='<span style="color:'+colors[type]+';font-size:16px">'+icons[type]+'</span><span style="color:#e2e8f0">'+msg+'</span>';
  document.body.appendChild(t);toastTimeout=setTimeout(function(){t.remove();},4000);
}

document.addEventListener('DOMContentLoaded',function(){
  capturerPosition();
  actualiserStats();
  renderGraphique();
});
</script>
</body>
</html>