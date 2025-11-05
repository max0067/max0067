<?php
/**
 * Session Manager V2 - Modern et sécurisé
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

class SessionManager {
    private $db;
    private $currentUser = null;

    public function __construct() {
        $this->db = Database::getInstance();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Charger l'utilisateur si authentifié
        if (isset($_SESSION['token'])) {
            $this->currentUser = $this->db->getUserBySession($_SESSION['token']);

            if (!$this->currentUser) {
                $this->logout();
            }
        }
    }

    public function isAuthenticated() {
        return $this->currentUser !== null;
    }

    public function getCurrentUser() {
        return $this->currentUser;
    }

    public function login($username, $password) {
        $user = $this->db->authenticateUser($username, $password);

        if ($user) {
            $token = $this->db->createSession($user['id']);

            if ($token) {
                $_SESSION['token'] = $token;
                $_SESSION['user_id'] = $user['id'];
                $this->currentUser = $user;
                return true;
            }
        }

        return false;
    }

    public function logout() {
        if (isset($_SESSION['token'])) {
            $this->db->deleteSession($_SESSION['token']);
        }

        session_destroy();
        $_SESSION = [];
        $this->currentUser = null;
    }

    public function isAdmin() {
        return $this->currentUser && $this->currentUser['role'] === 'admin';
    }

    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            if ($this->isAjaxRequest()) {
                jsonResponse(false, [], 'Authentication required', 401);
            }
            header('Location: /v2/login');
            exit;
        }
    }

    public function requireAdmin() {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            if ($this->isAjaxRequest()) {
                jsonResponse(false, [], 'Admin access required', 403);
            }
            header('Location: /v2/');
            exit;
        }
    }

    private function isAjaxRequest() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['CONTENT_TYPE']) &&
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    }
}
