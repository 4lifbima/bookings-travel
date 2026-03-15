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
        $avatar_path = null;
        $has_new_avatar = isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name']);

        if ($has_new_avatar) {
            $file = $_FILES['avatar'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status'=>'error','message'=>'Upload foto profil gagal']);
                $conn->close();
                exit;
            }

            $max_size = 5 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                echo json_encode(['status'=>'error','message'=>'Ukuran foto profil maksimal 5MB']);
                $conn->close();
                exit;
            }

            $allowed_ext = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext, true)) {
                echo json_encode(['status'=>'error','message'=>'Format foto profil harus JPG, PNG, atau WEBP']);
                $conn->close();
                exit;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed_mime = ['image/jpeg','image/png','image/webp'];
            if (!in_array($mime, $allowed_mime, true)) {
                echo json_encode(['status'=>'error','message'=>'File foto profil tidak valid']);
                $conn->close();
                exit;
            }

            $upload_dir = __DIR__ . '/../storage/assets/profile/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
                echo json_encode(['status'=>'error','message'=>'Folder foto profil tidak tersedia']);
                $conn->close();
                exit;
            }

            $filename = 'profile_' . (int)$_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $upload_dir . $filename;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                echo json_encode(['status'=>'error','message'=>'Gagal menyimpan foto profil']);
                $conn->close();
                exit;
            }

            $avatar_path = 'storage/assets/profile/' . $filename;
        }

        if ($has_new_avatar) {
            $current_q = $conn->query("SELECT avatar FROM users WHERE id=" . (int)$_SESSION['user_id']);
            $current = $current_q ? $current_q->fetch_assoc() : null;
            if ($current && !empty($current['avatar']) && strpos($current['avatar'], 'storage/assets/profile/') === 0) {
                $old_file = __DIR__ . '/../' . $current['avatar'];
                if (is_file($old_file)) unlink($old_file);
            }

            $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, avatar=? WHERE id=?");
            $stmt->bind_param('sssi', $name, $phone, $avatar_path, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $phone, $_SESSION['user_id']);
        }

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