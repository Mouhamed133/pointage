<?php
$erreur = $erreur ?? '';
$token  = $token  ?? ($_GET['token'] ?? '');

$estErreurFatale = !empty($erreur) && (
    strpos($erreur, 'expire') !== false ||
    strpos($erreur, 'invalide') !== false
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activer mon compte — PointagePro</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Space Grotesk',sans-serif;background:#0a0e1a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#0f1629;border:1px solid #1e2a4a;border-radius:20px;padding:36px;width:100%;max-width:420px}
input{background:#0a0e1a;border:1px solid #1e2a4a;border-radius:10px;color:#e2e8f0;padding:12px 14px;font-size:14px;font-family:inherit;outline:none;width:100%;transition:border-color .2s}
input:focus{border-color:#34d399}
.btn-primary{background:#059669;color:white;border:none;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;width:100%;font-family:inherit;transition:all .2s}
.btn-primary:hover{background:#047857}
.error-box{background:#2d1414;border:1px solid #7f1d1d;color:#f87171;padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;line-height:1.5}
label{font-size:12px;color:#64748b;display:block;margin-bottom:6px}
a.link{color:#34d399;text-decoration:none;font-size:13px}
a.link:hover{text-decoration:underline}
</style>
</head>
<body>
  <div class="card">
    <div style="text-align:center;margin-bottom:28px">
      <div style="width:48px;height:48px;background:#059669;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="width:24px;height:24px"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h1 style="font-size:20px;font-weight:700;color:white;margin-bottom:4px">Activer mon compte</h1>
      <p style="font-size:13px;color:#64748b">Choisissez votre mot de passe pour acceder a PointagePro</p>
    </div>

    <?php if (!empty($erreur)): ?>
      <div class="error-box"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($estErreurFatale): ?>
      <p style="text-align:center"><a href="index.php?route=login" class="link">Retour a la connexion</a></p>
    <?php else: ?>
      <form method="POST" action="index.php?route=activer&token=<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div style="margin-bottom:16px">
          <label>Mot de passe</label>
          <input type="password" name="password" placeholder="Au moins 6 caracteres" required>
        </div>
        <div style="margin-bottom:24px">
          <label>Confirmer le mot de passe</label>
          <input type="password" name="password_confirm" placeholder="..." required>
        </div>
        <button type="submit" class="btn-primary">Activer mon compte</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>