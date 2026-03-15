<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'danau_paisupok');
define('BASE_URL', 'http://10.242.70.129/pemesanan-tiket');
define('SITE_NAME', 'Danau Paisupok');
define('PRIMARY_COLOR', '#f54518');

function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_ticket_code() {
    return 'TKT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

function generate_booking_code() {
    return 'BKG-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

function format_rupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}