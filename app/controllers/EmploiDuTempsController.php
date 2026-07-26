<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/EmploiDuTemps.php';

class EmploiDuTempsController
{
    private PDO $db;
    private EmploiDuTemps $model;


    public function __construct()
    {
        $database    = new Database();
        $this->db    = $database->getConnection();
        $this->model = new EmploiDuTemps();
    }



    // ============================================
    // LISTE TOUS LES CRÉNEAUX (AJAX)
    // ============================================
    public function liste(): void
    {
        $tous = $this->model->tous();

        $this->json(true, '', $tous);
    }



    // ============================================
    // LISTE DES COHORTES POUR LE MODAL (AJAX)
    // ============================================
    public function listeCohortes(): void
    {
        try {

            $stmt = $this->db->query("
                SELECT id, nom
                FROM cohortes
                ORDER BY nom ASC
            ");


            $cohortes = $stmt->fetchAll(PDO::FETCH_ASSOC);


            $this->json(
                true,
                '',
                $cohortes
            );


        } catch (Exception $e) {

            $this->json(
                false,
                $e->getMessage()
            );

        }
    }



    // ============================================
    // CRÉNEAUX D'UNE COHORTE (AJAX)
    // ============================================
    public function parCohorte(): void
    {
        $cohorte_id = intval($_GET['cohorte_id'] ?? 0);


        if (!$cohorte_id) {

            $this->json(false, 'Cohorte requise');
            return;

        }


        $creneaux = $this->model->parCohorte($cohorte_id);


        $this->json(true, '', [
            'creneaux' => $creneaux
        ]);
    }




    // ============================================
    // SAUVEGARDER UN CRÉNEAU
    // ============================================
    public function sauvegarder(): void
    {
        $this->checkAdmin();


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            $this->json(false, 'Méthode non autorisée');
            return;

        }


        $cohorte_id = intval($_POST['cohorte_id'] ?? 0);
        $jour       = trim($_POST['jour'] ?? '');
        $debut      = trim($_POST['heure_debut'] ?? '');
        $fin        = trim($_POST['heure_fin'] ?? '');



        if (!$cohorte_id || empty($jour) || empty($debut) || empty($fin)) {

            $this->json(false, 'Tous les champs sont requis');
            return;

        }



        $joursValides = array_keys(EmploiDuTemps::JOURS);


        if (!in_array($jour, $joursValides)) {

            $this->json(false, 'Jour invalide');
            return;

        }



        if ($debut >= $fin) {

            $this->json(false, 'L\'heure de fin doit être après l\'heure de début');
            return;

        }



        $ok = $this->model->sauvegarder(
            $cohorte_id,
            $jour,
            $debut,
            $fin
        );



        if ($ok) {

            $this->logAction(
                'update',
                'emplois_du_temps'
            );


            $this->json(
                true,
                'Créneau enregistré avec succès'
            );


        } else {


            $this->json(
                false,
                'Erreur lors de l\'enregistrement'
            );

        }

    }




    // ============================================
    // SUPPRIMER UN CRÉNEAU
    // ============================================
    public function supprimer(): void
    {
        $this->checkAdmin();


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            $this->json(false, 'Méthode non autorisée');
            return;

        }



        $id = intval($_POST['id'] ?? 0);



        if (!$id) {

            $this->json(false, 'ID invalide');
            return;

        }



        $ok = $this->model->supprimer($id);



        if ($ok) {

            $this->logAction(
                'delete',
                'emplois_du_temps'
            );


            $this->json(
                true,
                'Créneau supprimé'
            );


        } else {


            $this->json(
                false,
                'Erreur lors de la suppression'
            );

        }

    }




    // ============================================
    // VÉRIFIER SI POINTAGE AUTORISÉ
    // ============================================
    public function verifier(): void
    {
        $cohorte_id = $_SESSION['user']['cohorte_id'] ?? 0;


        $result = $this->model->verifierPointage(
            $cohorte_id
        );


        $this->json(
            $result['statut'] === 'ok',
            $result['message'],
            [
                'statut'  => $result['statut'],
                'creneau' => $result['creneau'],
            ]
        );
    }




    // ============================================
    // HELPERS
    // ============================================

    private function checkAdmin(): void
    {
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {

            $this->json(
                false,
                'Accès refusé'
            );

            exit;
        }
    }




    private function json(
        bool $success,
        string $message,
        array $data = []
    ): void
    {
        header('Content-Type: application/json; charset=utf-8');


        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ]);


        exit;
    }




    private function logAction(
        string $action,
        string $entity = ''
    ): void
    {
        try {

            $userId = $_SESSION['user']['id'] ?? null;

            $ip = $this->getIp();


            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs 
                (user_id, action, entity, ip) 
                VALUES (?, ?, ?, ?)"
            );


            $stmt->execute([
                $userId,
                $action,
                $entity,
                $ip
            ]);


        } catch (\Exception $e) {

        }
    }




    private function getIp(): string
    {
        foreach (
            [
                'HTTP_CF_CONNECTING_IP',
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED_FOR'
            ]
            as $h
        ) {

            if (!empty($_SERVER[$h])) {

                $ip = trim(
                    explode(',', $_SERVER[$h])[0]
                );


                if (
                    filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE |
                        FILTER_FLAG_NO_RES_RANGE
                    )
                ) {

                    return $ip;
                }
            }
        }


        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';


        return (
            $ip === '::1' ||
            $ip === '127.0.0.1'
        )
        ? '127.0.0.1 (local)'
        : $ip;
    }
}