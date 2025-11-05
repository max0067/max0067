<?php
require_once '../config.php';

// Handle logout via GET
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    redirect('/login.php');
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'login':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            jsonResponse(['success' => false, 'error' => 'Username and password required'], 400);
        }

        if (login($username, $password)) {
            $user = getCurrentUser();
            jsonResponse([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
        }
        break;

    case 'logout':
        logout();
        jsonResponse(['success' => true]);
        break;

    case 'me':
        $user = requireApiAuth();
        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
        break;

    case 'change_password':
        $user = requireApiAuth();
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            jsonResponse(['success' => false, 'error' => 'Both passwords required'], 400);
        }

        // Verify current password
        $verifyUser = authenticateUser($user['username'], $currentPassword);
        if (!$verifyUser) {
            jsonResponse(['success' => false, 'error' => 'Current password incorrect'], 400);
        }

        // Update password
        if (updateUser($user['id'], ['password' => $newPassword])) {
            jsonResponse(['success' => true, 'message' => 'Password updated successfully']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to update password'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}
