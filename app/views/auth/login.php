<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TellyTech Presences — Connexion</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    /* Fond de page Gris Très Clair (#F5F7FA) */
    body{font-family:'Space Grotesk',sans-serif;background:#F5F7FA;color:#2F4F7F;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:18px}
    .mono{font-family:'JetBrains Mono',monospace}
    /* Carte Blanche avec une ombre légère et propre */
    .card{background:#FFFFFF;border:1px solid #DCE3EC;border-radius:16px;box-shadow:0 4px 15px rgba(35,64,106,0.05)}
    /* Champs de formulaire avec Gris Moyen (#DCE3EC) */
    input{width:100%;height:50px;background:#FFFFFF;border:1px solid #DCE3EC;border-radius:10px;padding:0 14px;color:#23406A;font-size:14px;outline:none;transition:0.2s;font-family:inherit}
    input:focus{border-color:#2F4F7F;box-shadow:0 0 0 3px rgba(47,79,127,0.1)}
    input::placeholder{color:#6B7280}
    label{display:block;margin-bottom:6px;font-size:12px;color:#23406A;font-weight:600}
    /* Bouton Bleu Foncé (#23406A) et survol Bleu Clair (#5B7DA8) */
    .btn-primary{width:100%;height:50px;border:none;border-radius:10px;background:#23406A;color:#FFFFFF;font-size:14px;font-weight:600;cursor:pointer;transition:0.2s;font-family:inherit}
    .btn-primary:hover{background:#5B7DA8}
    /* Alertes et messages ajustés à la charte */
    .alert-error{background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;border-radius:10px;padding:10px 12px;font-size:13px}
    .alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;border-radius:10px;padding:10px 12px;font-size:13px}
    .alert-scan{background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;border-radius:10px;padding:14px 16px;font-size:13px;text-align:center}
    .alert-scan-error{background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;border-radius:10px;padding:14px 16px;font-size:13px;text-align:center}
    .divider{border:none;border-top:1px solid #DCE3EC;margin:18px 0}
    @keyframes fadeIn{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
    .animate-in{animation:fadeIn .4s ease}
  </style>
</head>
<body>

<div class="w-full max-w-sm animate-in">

  <div class="text-center mb-6">
    <div class="w-14 h-14 bg-[#23406A] rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-md shadow-[#23406A]/10 border border-[#5B7DA8]/20">
      <span class="text-white text-xl font-bold tracking-wider">PP</span>
    </div>
    <h1 class="text-2xl font-bold tracking-tight text-[#23406A]">
      PointagePro <span class="text-[#5B7DA8] font-medium">TellyTech</span>
    </h1>
    <p class="text-xs text-[#6B7280] mt-1.5 mono uppercase tracking-wider">
      Espace émargement
    </p>
  </div>

  <div class="card p-6">

    <h2 class="text-lg font-bold text-[#23406A] mb-1">Connexion</h2>
    <p class="text-xs text-[#6B7280] mb-5">Veuillez renseigner vos identifiants</p>

    <?php if (!empty($_GET['scan']) && $_GET['scan'] === '1'): ?>
      <div class="alert-scan mb-4">
        <p style="font-size:22px;margin-bottom:6px">📷</p>
        <p style="font-weight:700;margin-bottom:4px;">QR Code scanné avec succès !</p>
        <p style="color:#6B7280;font-size:12px">Connectez-vous pour valider et enregistrer votre présence.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($_GET['scan_erreur'])): ?>
      <?php
        $msgsErreur = [
          'token_invalide' => 'Ce QR Code n\'est pas valide pour la session actuelle.',
          'qr_expire'      => 'Ce QR Code a expiré. Veuillez scanner le code mis à jour sur l\'écran.',
        ];
        $msgErreur = $msgsErreur[$_GET['scan_erreur']] ?? 'QR Code invalide.';
      ?>
      <div class="alert-scan-error mb-4">
        <p style="font-weight:700;margin-bottom:4px">⚠️ Erreur de validation</p>
        <p style="font-size:12px"><?= htmlspecialchars($msgErreur) ?></p>
      </div>
    <?php endif; ?>
    <?php if (!empty($erreur)): ?>
      <div class="alert-error mb-4"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if (!empty($_GET['active'])): ?>
      <div class="alert-success mb-4">&#10003; Compte activé avec succès ! Vous pouvez vous connecter.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['inscription_desactivee'])): ?>
      <div class="alert-error mb-4">L'auto-inscription est fermée. Veuillez vous adresser à l'administration.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['inactive'])): ?>
      <div class="alert-error mb-4">Votre compte est actuellement inactif ou en attente de validation.</div>
    <?php endif; ?>

    <?php if (!empty($_GET['reset'])): ?>
      <div class="alert-success mb-4">&#10003; Mot de passe réinitialisé. Connectez-vous.</div>
    <?php endif; ?>

    <form method="POST" action="index.php?route=login<?= !empty($_GET['scan']) ? '&scan=1' : '' ?>" class="space-y-4">

      <div>
        <label>Adresse Email</label>
        <input type="email" name="email" placeholder="votre.nom@telly-tech.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div>
        <label>Mot de passe</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>

      <div class="pt-1">
        <button type="submit" class="btn-primary shadow-sm">
          <?= !empty($_GET['scan']) ? '✓ Se connecter et émarger' : 'Se connecter' ?>
        </button>
      </div>

    </form>

    <hr class="divider">

    <p class="text-center text-xs">
      <a href="index.php?route=forgot" class="text-[#6B7280] hover:text-[#5B7DA8] transition-colors font-medium">
        Mot de passe oublié ?
      </a>
    </p>

  </div>

  <p class="text-center text-[11px] text-[#6B7280] mt-5 mono">
    TellyTech Ecosystem — <?= date('Y') ?>
  </p>

</div>

</body>
</html>