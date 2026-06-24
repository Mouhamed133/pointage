<?php $erreur = $erreur ?? ''; $email = $email ?? ''; $token = $token ?? ''; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PointagePro — Nouveau mot de passe</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Space Grotesk',sans-serif;background:#0a0e1a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
    .card{background:#0f1629;border:1px solid #1e2a4a;border-radius:20px}
    .input-group{position:relative}
    .input-group svg{position:absolute;left:16px;top:50%;transform:translateY(-50%);width:20px;height:20px;color:#475569;pointer-events:none}
    input{background:#0a0e1a;border:1px solid #1e2a4a;border-radius:12px;color:#e2e8f0;padding:16px 16px 16px 48px;font-size:15px;font-family:inherit;outline:none;transition:border-color .2s;width:100%;height:50px}
    input:focus{border-color:#34d399;box-shadow:0 0 0 3px rgba(52,211,153,.1)}
    input::placeholder{color:#334155}
    .btn-primary{background:#059669;color:white;border:none;padding:16px 20px;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit;width:100%;height:50px}
    .btn-primary:hover{background:#047857;transform:translateY(-1px)}
    .alert-error{background:#2d1414;border:1px solid #7f1d1d;color:#f87171;border-radius:10px;padding:14px 16px;font-size:14px}
    @keyframes slide-in{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
    .animate-in{animation:slide-in .4s ease forwards}
    label{font-size:13px;color:#64748b;display:block;margin-bottom:8px;font-weight:500}
    .link{color:#34d399;text-decoration:none;font-weight:600}
    .divider{border:none;border-top:1px solid #1e2a4a;margin:24px 0}
  </style>
</head>
<body>
<div class="w-full max-w-md px-4 animate-in">

  <div class="text-center mb-8">
    <div class="w-16 h-16 bg-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
      <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" class="w-8 h-8">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0110 0v4"/>
      </svg>
    </div>
    <h1 class="text-3xl font-bold text-white">PointagePro</h1>
    <p class="text-sm text-slate-400 mt-2">Nouveau mot de passe</p>
  </div>

  <div class="card p-8">
    <h2 class="text-xl font-700 text-white mb-1">Choisir un nouveau mot de passe</h2>
    <p class="text-sm text-slate-400 mb-6">Pour le compte : <span class="text-emerald-400"><?= htmlspecialchars($email) ?></span></p>

    <?php if ($erreur): ?>
      <div class="alert-error mb-5">&#9888; <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?route=reset&token=<?= htmlspecialchars($token) ?>" class="space-y-5">
      <div>
        <label>Nouveau mot de passe</label>
        <div class="input-group">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
          </svg>
          <input type="password" name="password" placeholder="••••••••••" required minlength="6" autofocus>
        </div>
      </div>

      <div>
        <label>Confirmer le mot de passe</label>
        <div class="input-group">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
          </svg>
          <input type="password" name="password_confirm" placeholder="••••••••••" required minlength="6">
        </div>
      </div>

      <button type="submit" class="btn-primary">Enregistrer le nouveau mot de passe</button>
    </form>

    <hr class="divider">
    <p class="text-center text-sm text-slate-400">
      <a href="index.php?route=login" class="link">&larr; Retour a la connexion</a>
    </p>
  </div>
</div>
</body>
</html>