<?php
// ajax/admin.php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_admin()) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ============================
    // BOOKINGS
    // ============================
    case 'get_all_bookings':
        $conn = db_connect();
        $filter = sanitize($_GET['filter'] ?? 'all');
        $where = $filter !== 'all' ? "WHERE b.status='$filter'" : '';

        $result = $conn->query("SELECT b.*, u.name as user_name FROM bookings b JOIN users u ON b.user_id=u.id $where ORDER BY b.created_at DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['total_amount_formatted'] = format_rupiah($row['total_amount']);
            $row['visit_date_formatted'] = date('d M Y', strtotime($row['visit_date']));
            $data[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        $conn->close();
        break;

    case 'update_booking_status':
        $conn = db_connect();
        $code = sanitize($_POST['code'] ?? '');
        $status = sanitize($_POST['status'] ?? '');
        $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

        if (!$code || !in_array($status, $valid_statuses)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
            $conn->close(); exit;
        }

        $booking = $conn->query("SELECT * FROM bookings WHERE booking_code='$code'")->fetch_assoc();
        if (!$booking) {
            echo json_encode(['status' => 'error', 'message' => 'Booking tidak ditemukan']);
            $conn->close(); exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE booking_code=?");
            $stmt->bind_param('ss', $status, $code);
            $stmt->execute();

            // Generate ticket codes when confirming
            if ($status === 'confirmed') {
                $items = $conn->query("SELECT * FROM booking_items WHERE booking_id={$booking['id']}");
                while ($item = $items->fetch_assoc()) {
                    // Check if tickets already exist
                    $existing = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE booking_item_id={$item['id']}")->fetch_assoc()['c'];
                    if ($existing == 0) {
                        for ($i = 0; $i < $item['quantity']; $i++) {
                            $ticket_code = generate_ticket_code();
                            $ins = $conn->prepare("INSERT INTO tickets (ticket_code, booking_id, booking_item_id, status) VALUES (?,?,?,'active')");
                            $ins->bind_param('sii', $ticket_code, $booking['id'], $item['id']);
                            $ins->execute();
                        }
                    }
                }
            }

            $conn->commit();
            $labels = ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan', 'completed' => 'Selesai'];
            echo json_encode(['status' => 'success', 'message' => 'Status berhasil diubah menjadi ' . $labels[$status]]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status: ' . $e->getMessage()]);
        }
        $conn->close();
        break;

    case 'get_pending_count':
        $conn = db_connect();
        $count = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];
        echo json_encode(['status' => 'success', 'count' => $count]);
        $conn->close();
        break;

    // ============================
    // TICKETS
    // ============================
    case 'get_tickets':
        $conn = db_connect();
        $filter = sanitize($_GET['filter'] ?? 'all');
        $where = $filter !== 'all' ? "WHERE t.status='$filter'" : '';

        $result = $conn->query("SELECT t.*, b.booking_code, b.visit_date, u.name as user_name, tc.name as category_name
            FROM tickets t
            JOIN bookings b ON t.booking_id = b.id
            JOIN booking_items bi ON t.booking_item_id = bi.id
            JOIN ticket_categories tc ON bi.ticket_category_id = tc.id
            JOIN users u ON b.user_id = u.id
            $where
            ORDER BY t.created_at DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data]);
        $conn->close();
        break;

    case 'mark_ticket_used':
        $conn = db_connect();
        $code = sanitize($_POST['code'] ?? '');
        $ticket = $conn->query("SELECT * FROM tickets WHERE ticket_code='$code'")->fetch_assoc();
        if (!$ticket) {
            echo json_encode(['status' => 'error', 'message' => 'Tiket tidak ditemukan']);
            $conn->close(); exit;
        }
        if ($ticket['status'] !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Tiket sudah tidak aktif']);
            $conn->close(); exit;
        }
        $conn->query("UPDATE tickets SET status='used', used_at=NOW() WHERE ticket_code='$code'");
        echo json_encode(['status' => 'success', 'message' => 'Tiket berhasil ditandai sebagai digunakan']);
        $conn->close();
        break;

    // ============================
    // USERS
    // ============================
    case 'get_users':
        $conn = db_connect();
        $result = $conn->query("SELECT id, name, email, phone, role, is_active, created_at FROM users ORDER BY created_at DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data]);
        $conn->close();
        break;

    case 'toggle_user_status':
        $conn = db_connect();
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 1);

        // Don't deactivate yourself
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menonaktifkan akun sendiri']);
            $conn->close(); exit;
        }

        $stmt = $conn->prepare("UPDATE users SET is_active=? WHERE id=?");
        $stmt->bind_param('ii', $status, $id);
        $stmt->execute();
        $msg = $status ? 'Akun berhasil diaktifkan' : 'Akun berhasil dinonaktifkan';
        echo json_encode(['status' => 'success', 'message' => $msg]);
        $conn->close();
        break;

    // ============================
    // CATEGORIES
    // ============================
    case 'save_category':
        $conn = db_connect();
        $id = (int)($_POST['cat_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $quota = (int)($_POST['quota_per_day'] ?? 100);
        $icon = sanitize($_POST['icon'] ?? 'mdi:ticket');
        $color = sanitize($_POST['color'] ?? '#f54518');

        if (!$name || $price < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
            $conn->close(); exit;
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE ticket_categories SET name=?, description=?, price=?, quota_per_day=?, icon=?, color=? WHERE id=?");
            $stmt->bind_param('ssdissi', $name, $desc, $price, $quota, $icon, $color, $id);
            $msg = 'Kategori berhasil diperbarui';
        } else {
            $stmt = $conn->prepare("INSERT INTO ticket_categories (name, description, price, quota_per_day, icon, color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssdiss', $name, $desc, $price, $quota, $icon, $color);
            $msg = 'Kategori berhasil ditambahkan';
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $conn->error]);
        }
        $conn->close();
        break;

    case 'toggle_cat_status':
        $conn = db_connect();
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 1);
        $conn->query("UPDATE ticket_categories SET is_active=$status WHERE id=$id");
        echo json_encode(['status' => 'success', 'message' => $status ? 'Kategori diaktifkan' : 'Kategori dinonaktifkan']);
        $conn->close();
        break;

    // ============================
    // SETTINGS
    // ============================
    case 'save_settings':
        $conn = db_connect();
        $fields = ['open_time', 'close_time', 'max_booking_days', 'min_booking_hours', 'whatsapp_number', 'location', 'about'];
        $conn->begin_transaction();
        try {
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $val = sanitize($_POST[$field]);
                    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
                    $stmt->bind_param('sss', $field, $val, $val);
                    $stmt->execute();
                }
            }
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
        $conn->close();
        break;

    // ============================
    // ANNOUNCEMENTS
    // ============================
    case 'save_announcement':
        $conn = db_connect();
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        $type = sanitize($_POST['type'] ?? 'info');
        $valid_types = ['info', 'warning', 'success', 'danger'];

        if (!$title || !$content) {
            echo json_encode(['status' => 'error', 'message' => 'Judul dan isi pengumuman wajib diisi']);
            $conn->close(); exit;
        }
        if (!in_array($type, $valid_types)) $type = 'info';

        $stmt = $conn->prepare("INSERT INTO announcements (title, content, type) VALUES (?,?,?)");
        $stmt->bind_param('sss', $title, $content, $type);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil ditambahkan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan pengumuman']);
        }
        $conn->close();
        break;

    case 'toggle_ann_status':
        $conn = db_connect();
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 1);
        $conn->query("UPDATE announcements SET is_active=$status WHERE id=$id");
        echo json_encode(['status' => 'success', 'message' => $status ? 'Pengumuman ditampilkan' : 'Pengumuman disembunyikan']);
        $conn->close();
        break;

    case 'delete_announcement':
        $conn = db_connect();
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM announcements WHERE id=$id");
        echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil dihapus']);
        $conn->close();
        break;

    // ============================
    // REPORTS
    // ============================
    case 'get_monthly_report':
        $conn = db_connect();
        $result = $conn->query("
            SELECT
                DATE_FORMAT(created_at, '%M %Y') as month,
                DATE_FORMAT(created_at, '%Y-%m') as month_key,
                COUNT(*) as total_bookings,
                SUM(total_tickets) as total_tickets,
                SUM(CASE WHEN status IN ('confirmed','completed') THEN total_amount ELSE 0 END) as revenue,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM bookings
            GROUP BY month_key
            ORDER BY month_key DESC
            LIMIT 12
        ");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['revenue_formatted'] = format_rupiah($row['revenue']);
            $data[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        $conn->close();
        break;

    case 'get_revenue_trend':
        $conn = db_connect();
        $days = (int)($_GET['days'] ?? 7);
        if (!in_array($days, [7, 30, 90])) $days = 7;

        $result = $conn->query("
            SELECT DATE(created_at) as day, SUM(total_amount) as revenue
            FROM bookings
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
            AND status IN ('confirmed','completed')
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");

        $labels = $data_arr = [];
        while ($row = $result->fetch_assoc()) {
            $labels[] = date('d M', strtotime($row['day']));
            $data_arr[] = (float)$row['revenue'];
        }
        echo json_encode(['status' => 'success', 'labels' => $labels, 'data' => $data_arr]);
        $conn->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
}