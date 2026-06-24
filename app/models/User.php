<?php

require_once __DIR__ . '/../../config/Database.php';

/**
 * app/models/User.php
 * Table : users
 * Colonnes : id (uuid), nom, email, password_hash, role, department,
 *            is_active, created_at
 *
 * Reprend exactement les requetes deja utilisees dans AuthController
 * et UserController, regroupees ici pour reutilisation.
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ============================================
    // LECTURE
    // ============================================

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    /** Verifie email + mot de passe pour la connexion. Retourne l'utilisateur (sans hash) si valide. */
    public function verifierIdentifiants(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        unset($user['password_hash']);
        return $user;
    }

    /** Nombre total d'etudiants actifs (utilise dans dashboard/stats) */
    public function compterEtudiantsActifs(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE is_active = 1 AND role = 'etudiant'");
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /** Liste des etudiants actifs, avec recherche et filtre departement optionnels */
    public function listeEtudiants(string $search = '', string $dept = ''): array
    {
        $sql    = "SELECT id, nom, email, role, department, is_active, created_at FROM users WHERE role = 'etudiant' AND is_active = 1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (nom LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($dept)) {
            $sql .= " AND department = ?";
            $params[] = $dept;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Comptes etudiants en attente d'approbation (is_active = 0) */
    public function listeEnAttente(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nom, email, department, created_at
            FROM users
            WHERE is_active = 0 AND role = 'etudiant'
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ============================================
    // ECRITURE
    // ============================================

    /** Cree un compte etudiant en attente de validation (is_active = 0). Retourne l'id genere. */
    public function creerEnAttente(string $nom, string $email, string $department, string $password): string
    {
        $id   = $this->uuid();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO users (id, nom, email, password_hash, role, department, is_active)
            VALUES (?, ?, ?, ?, 'etudiant', ?, 0)
        ");
        $stmt->execute([$id, $nom, $email, $hash, $department]);

        return $id;
    }

    /** Cree un compte etudiant deja actif (creation directe par un admin/manager). Retourne l'id genere. */
    public function creerActif(string $nom, string $email, string $department, string $password): string
    {
        $id   = $this->uuid();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO users (id, nom, email, password_hash, role, department, is_active)
            VALUES (?, ?, ?, ?, 'etudiant', ?, 1)
        ");
        $stmt->execute([$id, $nom, $email, $hash, $department]);

        return $id;
    }

    /**
     * Cree un compte etudiant invite par l'admin : pas de mot de passe utilisable
     * defini ici, compte inactif jusqu'a ce que l'etudiant clique le lien recu
     * par email et choisisse lui-meme son mot de passe (voir activerAvecMotDePasse).
     * Retourne l'id genere.
     */
    public function creerInviteParAdmin(string $nom, string $email, string $department): string
    {
        $id = $this->uuid();
        // Mot de passe temporaire et inutilisable : remplace a l'activation
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO users (id, nom, email, password_hash, role, department, is_active)
            VALUES (?, ?, ?, ?, 'etudiant', ?, 0)
        ");
        $stmt->execute([$id, $nom, $email, $hash, $department]);

        return $id;
    }

    /**
     * Active le compte et definit le mot de passe choisi par l'etudiant
     * au moment ou il accepte l'invitation par email.
     */
    public function activerAvecMotDePasse(string $email, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, is_active = 1 WHERE email = ?");
        return $stmt->execute([$hash, $email]);
    }

    public function activer(string $id): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function desactiver(string $id): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Suppression definitive (utilise pour refuser un compte en attente) */
    public function supprimerEnAttente(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND is_active = 0");
        return $stmt->execute([$id]);
    }

    public function mettreAJourMotDePasse(string $email, string $nouveauMotDePasse): bool
    {
        $hash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        return $stmt->execute([$hash, $email]);
    }

    /**
     * Met a jour les infos d'un etudiant depuis l'administration
     * (nom, email, departement, mot de passe optionnel). Utilise par
     * le bouton "Modifier" sur la page Etudiants.
     */
    public function modifierEtudiant(string $id, string $nom, string $email, string $department, ?string $password = null): bool
    {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET nom = ?, email = ?, department = ?, password_hash = ? WHERE id = ?");
            return $stmt->execute([$nom, $email, $department, $hash, $id]);
        }

        $stmt = $this->db->prepare("UPDATE users SET nom = ?, email = ?, department = ? WHERE id = ?");
        return $stmt->execute([$nom, $email, $department, $id]);
    }

    /**
     * Met a jour le profil de l'utilisateur connecte (nom + email toujours,
     * mot de passe seulement si fourni). Utilise par la page "Mon Profil".
     */
    public function mettreAJourProfil(string $id, string $nom, string $email, ?string $password = null): bool
    {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET nom = ?, email = ?, password_hash = ? WHERE id = ?");
            return $stmt->execute([$nom, $email, $hash, $id]);
        }

        $stmt = $this->db->prepare("UPDATE users SET nom = ?, email = ? WHERE id = ?");
        return $stmt->execute([$nom, $email, $id]);
    }

    // ============================================
    // HELPER
    // ============================================

    public function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}