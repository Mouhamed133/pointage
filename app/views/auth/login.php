<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PointagePro — Connexion</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Space Grotesk',sans-serif;background:#0a0e1a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:18px}
    .mono{font-family:'JetBrains Mono',monospace}
    .card{background:#0f1629;border:1px solid #1e2a4a;border-radius:16px}
    input{width:100%;height:50px;background:#0a0e1a;border:1px solid #1e2a4a;border-radius:10px;padding:0 14px;color:#e2e8f0;font-size:14px;outline:none;transition:0.2s;font-family:inherit}
    input:focus{border-color:#34d399;box-shadow:0 0 0 3px rgba(52,211,153,0.08)}
    input::placeholder{color:#475569}
    label{display:block;margin-bottom:6px;font-size:12px;color:#94a3b8;font-weight:500}
    .btn-primary{width:100%;height:50px;border:none;border-radius:10px;background:#059669;color:white;font-size:14px;font-weight:600;cursor:pointer;transition:0.2s;font-family:inherit}
    .btn-primary:hover{background:#047857}
    .alert-error{background:#2d1414;border:1px solid #7f1d1d;color:#fca5a5;border-radius:10px;padding:10px 12px;font-size:13px}
    .alert-success{background:#0d2a1e;border:1px solid #064e3b;color:#34d399;border-radius:10px;padding:10px 12px;font-size:13px}
    .alert-scan{background:#0f2a3f;border:1px solid #1e3a5f;color:#60a5fa;border-radius:10px;padding:14px 16px;font-size:13px;text-align:center}
    .alert-scan-error{background:#2d1414;border:1px solid #7f1d1d;color:#fca5a5;border-radius:10px;padding:14px 16px;font-size:13px;text-align:center}
    .divider{border:none;border-top:1px solid #1e2a4a;margin:18px 0}
    .link{color:#34d399;text-decoration:none;font-weight:600}
    .link:hover{color:#6ee7b7}
    @keyframes fadeIn{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
    .animate-in{animation:fadeIn .4s ease}
  </style>
</head>
<body>

<div class="w-full max-w-sm animate-in">

  <!-- LOGO -->
  <div class="text-center mb-5">
    <div class="w-12 h-12 bg-emerald-600 rounded-xl flex items-center justify-center mx-auto mb-3">
      <span class="text-white text-lg font-bold">P</span>
    </div>
    <h1 class="text-2xl font-bold text-white">PointagePro</h1>
    <p class="text-xs text-slate-400 mt-1 mono">Gestion des presences</p>
  </div>

  <!-- CARD -->
  <div class="card p-5">

    <h2 class="text-lg font-semibold text-white mb-1">Connexion</h2>
    <p class="text-xs text-slate-400 mb-5">Connectez-vous a votre compte</p>

    <!-- ============================================ -->
    <!-- BANDEAU SCAN QR — affiché si l'étudiant     -->
    <!-- arrive depuis le scan du QR mural            -->
    <!-- ============================================ -->
    <?php if (!empty($_GET['scan']) && $_GET['scan'] === '1'): ?>
      <div class="alert-scan mb-4">
        <p style="font-size:22px;margin-bottom:6px">📷</p>
        <p style="font-weight:600;margin-bottom:4px;color:#93c5fd">QR Code scanné !</p>
        <p style="color:#94a3b8;font-size:12px">Connectez-vous — votre pointage sera enregistré automatiquement.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($_GET['scan_erreur'])): ?>
      <?php
        $msgsErreur = [
          'token_invalide' => 'Ce QR Code n\'est pas reconnu.',
          'qr_expire'      => 'Ce QR Code a expiré. Demandez à l\'admin d\'en générer un nouveau.',
        ];
        $msgErreur = $msgsErreur[$_GET['scan_erreur']] ?? 'QR Code invalide.';
      ?>
      <div class="alert-scan-error mb-4">
        <p style="font-weight:600;margin-bottom:4px">⚠️ QR Code invalide</p>
        <p style="font-size:12px"><?= htmlspecialchars($msgErreur) ?></p>
      </div>
    <?php endif; ?>
    <!-- ============================================ -->

    <!-- AUTRES MESSAGES -->
    <?php if (!empty($erreur)): ?>
      <div class="alert-error mb-4"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if (!empty($_GET['active'])): ?>
      <div class="alert-success mb-4">&#10003; Compte active avec succes ! Vous pouvez vous connecter.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['inscription_desactivee'])): ?>
      <div class="alert-error mb-4">L'inscription libre n'est pas autorisee. Contactez votre administrateur pour obtenir un compte.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['inactive'])): ?>
      <div class="alert-error mb-4">Compte en attente d'activation. Verifiez votre boite mail.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['reset'])): ?>
      <div class="alert-success mb-4">&#10003; Mot de passe reinitialise avec succes ! Connectez-vous.</div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" action="index.php?route=login<?= !empty($_GET['scan']) ? '&scan=1' : '' ?>" class="space-y-4">

      <div>
        <label>Email</label>
        <input type="email" name="email" placeholder=""
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div>
        <label>Mot de passe</label>
        <input type="password" name="password" placeholder="" required>
      </div>

      <div class="pt-1">
        <button type="submit" class="btn-primary">
          <?= !empty($_GET['scan']) ? '✓ Se connecter et pointer' : 'Se connecter' ?>
        </button>
      </div>

    </form>

    <hr class="divider">

    <p class="text-center text-xs text-slate-400">
      <a href="index.php?route=forgot" style="color:#94a3b8;text-decoration:none">
        Mot de passe oublie ?
      </a>
    </p>

  </div>

  <p class="text-center text-[11px] text-slate-600 mt-4 mono">
    PointagePro v1.0 — <?= date('Y') ?>
  </p>

</div>

</body>
</html>