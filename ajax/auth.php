<?php
// ajax/auth.php
require_once '../includes/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['status'=>'error','message'=>'Email dan password wajib diisi']);
            exit;
        }

        $conn = db_connect();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $conn->close();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['status'=>'error','message'=>'Email atau password salah']);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        $redirect = $user['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php';
        echo json_encode(['status'=>'success','message'=>'Login berhasil','redirect'=>$redirect,'role'=>$user['role']]);
        break;

    case 'register':
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || !$password) {
            echo json_encode(['status'=>'error','message'=>'Nama, email, dan password wajib diisi']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status'=>'error','message'=>'Format email tidak valid']);
            exit;
        }
        if (strlen($password) < 8) {
            echo json_encode(['status'=>'error','message'=>'Password minimal 8 karakter']);
            exit;
        }

        $conn = db_connect();
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['status'=>'error','message'=>'Email sudah terdaftar']);
            $conn->close(); exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?,?,?,?,'user')");
        $stmt->bind_param('ssss', $name, $email, $phone, $hashed);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'Akun berhasil dibuat']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal membuat akun']);
        }
        $conn->close();
        break;

    case 'update_profile':
        if (!is_logged_in()) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        if (!$name) { echo json_encode(['status'=>'error','message'=>'Nama wajib diisi']); exit; }

        $conn = db_connect();
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
        $stmt->bind_param('ssi', $name, $phone, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            echo json_encode(['status'=>'success','message'=>'Profil berhasil diperbarui']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal memperbarui profil']);
        }
        $conn->close();
        break;

    case 'change_password':
        if (!is_logged_in()) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (!$old || !$new || strlen($new) < 8) {
            echo json_encode(['status'=>'error','message'=>'Password tidak valid']); exit;
        }

        $conn = db_connect();
        $user = $conn->query("SELECT password FROM users WHERE id=".$_SESSION['user_id'])->fetch_assoc();
        if (!password_verify($old, $user['password'])) {
            echo json_encode(['status'=>'error','message'=>'Password lama tidak cocok']); $conn->close(); exit;
        }

        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hashed, $_SESSION['user_id']);
        $stmt->execute();
        echo json_encode(['status'=>'success','message'=>'Password berhasil diubah']);
        $conn->close();
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action tidak valid']);
}