<?php
/**
 * Gestionnaire de sessions et authentification
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class SessionManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        // Démarrer la session PHP si pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public function isAuthenticated() {
        return isset($_SESSION['token']) && $this->getCurrentUser() !== null;
    }

    /**
     * Récupère l'utilisateur actuellement connecté
     */
    public function getCurrentUser() {
        if (!isset($_SESSION['token'])) {
            return null;
        }

        $user = $this->db->getUserBySession($_SESSION['token']);
        if (!$user) {
            $this->logout();
            return null;
        }

        return $user;
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login($username, $password) {
        $user = $this->db->authenticateUser($username, $password);
        if ($user) {
            $token = $this->db->createSession($user['id']);
            if ($token) {
                $_SESSION['token'] = $token;
                $_SESSION['user_id'] = $user['id'];
                log_message('INFO', "Utilisateur connecté: {$username}");
                return $user;
            }
        }
        return null;
    }

    /**
     * Déconnexion de l'utilisateur
     */
    public function logout() {
        if (isset($_SESSION['token'])) {
            $this->db->deleteSession($_SESSION['token']);
        }
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Vérifie si l'utilisateur actuel est admin
     */
    public function isAdmin() {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Middleware pour protéger les routes nécessitant une authentification
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            // Si c'est une requête AJAX/API
            if ($this->isAjaxRequest()) {
                json_error('Authentication required', 401);
            }
            // Sinon rediriger vers la page de connexion
            header('Location: /login');
            exit;
        }
    }

    /**
     * Middleware pour protéger les routes admin
     */
    public function requireAdmin() {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            if ($this->isAjaxRequest()) {
                json_error('Admin access required', 403);
            }
            header('Location: /');
            exit;
        }
    }

    /**
     * Vérifie si la requête est une requête AJAX/API
     */
    private function isAjaxRequest() {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['CONTENT_TYPE']) &&
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        ) || (
            isset($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        );
    }
}
