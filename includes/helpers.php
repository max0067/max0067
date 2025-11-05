<?php
/**
 * Fonctions utilitaires
 */

/**
 * Échapper les caractères HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formater une date en français
 */
function formatDate($date, $format = 'd/m/Y à H:i') {
    if (!$date) return '';

    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formater une date relative (il y a X heures/jours)
 */
function timeAgo($date) {
    if (!$date) return '';

    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return "à l'instant";
    }

    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "il y a {$minutes} minute" . ($minutes > 1 ? 's' : '');
    }

    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "il y a {$hours} heure" . ($hours > 1 ? 's' : '');
    }

    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return "il y a {$days} jour" . ($days > 1 ? 's' : '');
    }

    return formatDate($date);
}

/**
 * Tronquer un texte
 */
function truncate($text, $length = 150, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Nettoyer le HTML d'une description
 */
function cleanHTML($html) {
    // Supprimer les balises script et style
    $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
    $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);

    // Supprimer toutes les balises HTML sauf quelques-unes autorisées
    $allowedTags = '<p><br><strong><em><a><ul><ol><li>';
    $html = strip_tags($html, $allowedTags);

    return trim($html);
}

/**
 * Redirection
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Afficher un message flash
 */
function setFlash($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Récupérer et supprimer le message flash
 */
function getFlash() {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

/**
 * Générer une URL absolue
 */
function url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Récupérer une variable GET/POST de manière sécurisée
 */
function input($key, $default = null) {
    if (isset($_POST[$key])) {
        return trim($_POST[$key]);
    }

    if (isset($_GET[$key])) {
        return trim($_GET[$key]);
    }

    return $default;
}

/**
 * Générer des boutons de pagination
 */
function pagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center">';

    // Bouton précédent
    if ($currentPage > 1) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Précédent</a>';
        $html .= '</li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Précédent</span></li>';
    }

    // Numéros de pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }

    // Bouton suivant
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Suivant</a>';
        $html .= '</li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Suivant</span></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}
