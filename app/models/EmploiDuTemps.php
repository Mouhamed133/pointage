<?php

require_once __DIR__ . '/../../config/Database.php';


class EmploiDuTemps
{
    private PDO $db;


    const JOURS = [
        'lundi'    => 1,
        'mardi'    => 2,
        'mercredi' => 3,
        'jeudi'    => 4,
        'vendredi' => 5,
        'samedi'   => 6,
    ];



    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }



    // ============================================
    // Tous les créneaux
    // ============================================
    public function tous(): array
    {
        $stmt = $this->db->query("
            SELECT 
                e.*,
                c.nom AS cohorte_nom
            FROM emplois_du_temps e
            LEFT JOIN cohortes c 
                ON c.id = e.cohorte_id
            ORDER BY 
                c.nom,
                FIELD(
                    e.jour,
                    'lundi',
                    'mardi',
                    'mercredi',
                    'jeudi',
                    'vendredi',
                    'samedi'
                )
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    // ============================================
    // Créneaux d'une cohorte
    // ============================================
    public function parCohorte(int $cohorte_id): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM emplois_du_temps
            WHERE cohorte_id = ?
            AND actif = 1
            ORDER BY FIELD(
                jour,
                'lundi',
                'mardi',
                'mercredi',
                'jeudi',
                'vendredi',
                'samedi'
            )
        ");

        $stmt->execute([$cohorte_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    // ============================================
    // Créneau du jour d'une cohorte
    // ============================================
    public function creneauDuJour(int $cohorte_id): ?array
    {

        $joursInv = array_flip(self::JOURS);

        $numJour = (int)date('N');

        $jourNom = $joursInv[$numJour] ?? null;


        if (!$jourNom) {
            return null;
        }


        $stmt = $this->db->prepare("
            SELECT *
            FROM emplois_du_temps
            WHERE cohorte_id = ?
            AND jour = ?
            AND actif = 1
            LIMIT 1
        ");


        $stmt->execute([
            $cohorte_id,
            $jourNom
        ]);


        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        return $row ?: null;
    }




    // ============================================
    // Vérifier pointage
    // ============================================
    public function verifierPointage(int $cohorte_id): array
    {

        $creneau = $this->creneauDuJour($cohorte_id);



        if (!$creneau) {

            return [
                'statut'=>'pas_de_cours',
                'message'=>'Aucun cours prévu aujourd’hui.',
                'creneau'=>null
            ];

        }



        $heureActuelle = date('H:i:s');


        $debut = $creneau['heure_debut'];
        $fin   = $creneau['heure_fin'];



        $debutTolerance = date(
            'H:i:s',
            strtotime($debut)-1800
        );



        if($heureActuelle < $debutTolerance)
        {

            return [
                'statut'=>'hors_creneau',
                'message'=>'Pointage trop tôt.',
                'creneau'=>$creneau
            ];

        }



        if($heureActuelle > $fin)
        {

            return [
                'statut'=>'hors_creneau',
                'message'=>'Pointage terminé.',
                'creneau'=>$creneau
            ];

        }



        return [
            'statut'=>'ok',
            'message'=>'OK',
            'creneau'=>$creneau
        ];

    }




    // ============================================
    // Sauvegarder un créneau
    // ============================================
    public function sauvegarder(
        int $cohorte_id,
        string $jour,
        string $debut,
        string $fin
    ): bool
    {


        $stmt = $this->db->prepare("
            INSERT INTO emplois_du_temps
            (
                cohorte_id,
                jour,
                heure_debut,
                heure_fin,
                actif
            )

            VALUES
            (?, ?, ?, ?, 1)

            ON DUPLICATE KEY UPDATE

                heure_debut = VALUES(heure_debut),
                heure_fin = VALUES(heure_fin),
                actif = 1
        ");



        return $stmt->execute([
            $cohorte_id,
            $jour,
            $debut,
            $fin
        ]);

    }




    // ============================================
    // Supprimer
    // ============================================
    public function supprimer(int $id): bool
    {

        $stmt = $this->db->prepare(
            "DELETE FROM emplois_du_temps WHERE id=?"
        );


        return $stmt->execute([$id]);

    }




    // ============================================
    // Liste cohortes
    // ============================================
    public function cohortes(): array
    {

        $stmt = $this->db->query("
            SELECT id, nom
            FROM cohortes
            ORDER BY nom
        ");


        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

}