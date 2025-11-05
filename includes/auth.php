<?php
/**
 * Authentication functions
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['token']) && !empty($_SESSION['token']);
}

/**
 * Get current logged in user
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $user = getUserBySession($_SESSION['token']);
    if (!$user) {
        logout();
        return null;
    }

    return $user;
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }

    $user = getCurrentUser();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }

    return $user;
}

/**
 * Require admin role
 */
function requireAdmin() {
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        die('Access denied. Admin role required.');
    }
    return $user;
}

/**
 * Login user
 */
function login($username, $password) {
    $user = authenticateUser($username, $password);
    if ($user) {
        $token = createSession($user['id']);
        $_SESSION['token'] = $token;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

/**
 * Logout user
 */
function logout() {
    if (isset($_SESSION['token'])) {
        deleteSession($_SESSION['token']);
    }
    session_destroy();
    session_start();
}

/**
 * Check if user is admin
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Get user ID
 */
function getUserId() {
    $user = getCurrentUser();
    return $user ? $user['id'] : null;
}

/**
 * JSON response helper for API
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check authentication for API endpoints
 */
function requireApiAuth() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    }

    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Invalid session'], 401);
    }

    return $user;
}

/**
 * Check admin for API endpoints
 */
function requireApiAdmin() {
    $user = requireApiAuth();
    if ($user['role'] !== 'admin') {
        jsonResponse(['success' => false, 'error' => 'Admin access required'], 403);
    }
    return $user;
}
