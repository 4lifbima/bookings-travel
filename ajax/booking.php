<?php
// ajax/booking.php
require_once '../includes/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function format_date_id($date_str) {
    if (!$date_str) return '-';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $d = explode('-', $date_str);
    return (int)$d[2] . ' ' . $bulan[(int)$d[1]] . ' ' . $d[0];
}

switch ($action) {

    case 'create_booking':
        if (!is_logged_in()) { echo json_encode(['status'=>'error','message'=>'Silakan login terlebih dahulu']); exit; }

        $visit_date = sanitize($_POST['visit_date'] ?? '');
        $payment_method = sanitize($_POST['payment_method'] ?? '');
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        if (!$visit_date || empty($items)) {
            echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit;
        }
        if (!$payment_method) {
            echo json_encode(['status'=>'error','message'=>'Pilih metode pembayaran']); exit;
        }
        if (strtotime($visit_date) < strtotime('+23 hours')) {
            echo json_encode(['status'=>'error','message'=>'Tanggal kunjungan minimal 24 jam dari sekarang']); exit;
        }

        $payment_proof_path = null;
        if ($payment_method === 'transfer_bank') {
            if (!isset($_FILES['payment_proof']) || !is_uploaded_file($_FILES['payment_proof']['tmp_name'])) {
                echo json_encode(['status'=>'error','message'=>'Bukti pembayaran transfer wajib diupload']);
                exit;
            }

            $file = $_FILES['payment_proof'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status'=>'error','message'=>'Upload bukti pembayaran gagal']);
                exit;
            }

            $max_size_exclusive = 1 * 1024 * 1024; // Must be < 1MB
            if ($file['size'] >= $max_size_exclusive) {
                echo json_encode(['status'=>'error','message'=>'Ukuran bukti pembayaran harus di bawah 1MB']);
                exit;
            }

            $allowed_ext = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext, true)) {
                echo json_encode(['status'=>'error','message'=>'Format bukti pembayaran harus JPG, PNG, atau WEBP']);
                exit;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed_mime = ['image/jpeg','image/png','image/webp'];
            if (!in_array($mime, $allowed_mime, true)) {
                echo json_encode(['status'=>'error','message'=>'File bukti pembayaran tidak valid']);
                exit;
            }
        }

        $conn = db_connect();

        // Calculate totals
        $total_tickets = 0;
        $total_amount = 0;
        $validated_items = [];

        foreach ($items as $item) {
            $cat_id = (int)$item['category_id'];
            $qty = (int)$item['quantity'];
            if ($qty < 1 || $qty > 50) continue;

            $cat = $conn->query("SELECT * FROM ticket_categories WHERE id=$cat_id AND is_active=1")->fetch_assoc();
            if (!$cat) continue;

            $subtotal = $cat['price'] * $qty;
            $total_tickets += $qty;
            $total_amount += $subtotal;
            $validated_items[] = ['cat' => $cat, 'qty' => $qty, 'subtotal' => $subtotal];
        }

        if (empty($validated_items)) {
            echo json_encode(['status'=>'error','message'=>'Tidak ada tiket valid']); $conn->close(); exit;
        }

        $booking_code = generate_booking_code();
        $user_id = $_SESSION['user_id'];

        if ($payment_method === 'transfer_bank') {
            $upload_dir = __DIR__ . '/../storage/assets/api/tickets/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
                echo json_encode(['status'=>'error','message'=>'Folder upload bukti pembayaran tidak tersedia']);
                $conn->close();
                exit;
            }

            $proof_filename = 'proof_' . strtolower($booking_code) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $proof_target = $upload_dir . $proof_filename;
            if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $proof_target)) {
                echo json_encode(['status'=>'error','message'=>'Gagal menyimpan bukti pembayaran']);
                $conn->close();
                exit;
            }
            $payment_proof_path = 'storage/assets/api/tickets/' . $proof_filename;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO bookings (booking_code, user_id, visit_date, total_tickets, total_amount, payment_method, payment_proof, status) VALUES (?,?,?,?,?,?,?,'pending')");
            $stmt->bind_param('sissdss', $booking_code, $user_id, $visit_date, $total_tickets, $total_amount, $payment_method, $payment_proof_path);
            $stmt->execute();
            $booking_id = $conn->insert_id;

            foreach ($validated_items as $vi) {
                $stmt2 = $conn->prepare("INSERT INTO booking_items (booking_id, ticket_category_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)");
                $stmt2->bind_param('iiids', $booking_id, $vi['cat']['id'], $vi['qty'], $vi['cat']['price'], $vi['subtotal']);
                $stmt2->execute();
            }

            $conn->commit();
            echo json_encode(['status'=>'success','message'=>'Pesanan berhasil dibuat','booking_code'=>$booking_code]);
        } catch (Exception $e) {
            $conn->rollback();
            if ($payment_proof_path) {
                $abs_path = __DIR__ . '/../' . $payment_proof_path;
                if (is_file($abs_path)) unlink($abs_path);
            }
            echo json_encode(['status'=>'error','message'=>'Gagal membuat pesanan: '.$e->getMessage()]);
        }
        $conn->close();
        break;

    case 'get_my_bookings':
        if (!is_logged_in()) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
        $filter = sanitize($_GET['filter'] ?? 'all');
        $user_id = $_SESSION['user_id'];

        $conn = db_connect();
        $where = "WHERE b.user_id=$user_id";
        if ($filter !== 'all') { $where .= " AND b.status='$filter'"; }

        $result = $conn->query("SELECT b.*, u.name as user_name FROM bookings b JOIN users u ON b.user_id=u.id $where ORDER BY b.created_at DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['visit_date_formatted'] = format_date_id($row['visit_date']);
            $row['total_amount_formatted'] = format_rupiah($row['total_amount']);
            $row['created_at_formatted'] = date('d M Y H:i', strtotime($row['created_at']));
            $data[] = $row;
        }
        echo json_encode(['status'=>'success','data'=>$data]);
        $conn->close();
        break;

    case 'get_booking_detail':
        $code = sanitize($_GET['code'] ?? '');
        if (!$code) { echo json_encode(['status'=>'error','message'=>'Kode tidak valid']); exit; }

        $conn = db_connect();
        $stmt = $conn->prepare("SELECT b.*, u.name, u.email FROM bookings b JOIN users u ON b.user_id=u.id WHERE b.booking_code=?");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        if (!$booking) { echo json_encode(['status'=>'error','message'=>'Booking tidak ditemukan']); $conn->close(); exit; }

        // Check ownership (admin bisa lihat semua, user hanya miliknya)
        if (is_logged_in() && !is_admin() && $booking['user_id'] != $_SESSION['user_id']) {
            echo json_encode(['status'=>'error','message'=>'Akses ditolak']); $conn->close(); exit;
        }

        // Get items
        $items_q = $conn->query("SELECT bi.*, tc.name as category_name FROM booking_items bi JOIN ticket_categories tc ON bi.ticket_category_id=tc.id WHERE bi.booking_id={$booking['id']}");
        $items = [];
        while ($item = $items_q->fetch_assoc()) {
            $item['unit_price_formatted'] = format_rupiah($item['unit_price']);
            $item['subtotal_formatted'] = format_rupiah($item['subtotal']);
            $items[] = $item;
        }

        // Get tickets
        $tickets_q = $conn->query("SELECT t.* FROM tickets t WHERE t.booking_id={$booking['id']}");
        $tickets = [];
        while ($t = $tickets_q->fetch_assoc()) { $tickets[] = $t; }

        $booking['items'] = $items;
        $booking['tickets'] = $tickets;
        $booking['visit_date_formatted'] = format_date_id($booking['visit_date']);
        $booking['total_amount_formatted'] = format_rupiah($booking['total_amount']);
        $booking['created_at_formatted'] = date('d M Y H:i', strtotime($booking['created_at']));

        echo json_encode(['status'=>'success','data'=>$booking]);
        $conn->close();
        break;

    case 'track_booking':
        $code = sanitize($_GET['code'] ?? '');
        if (!$code) { echo json_encode(['status'=>'error','message'=>'Kode tidak valid']); exit; }

        $conn = db_connect();
        $stmt = $conn->prepare("SELECT b.*, u.name FROM bookings b JOIN users u ON b.user_id=u.id WHERE b.booking_code=?");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        if (!$booking) { echo json_encode(['status'=>'error','message'=>'Kode booking tidak ditemukan']); $conn->close(); exit; }

        $items_q = $conn->query("SELECT bi.*, tc.name as category_name FROM booking_items bi JOIN ticket_categories tc ON bi.ticket_category_id=tc.id WHERE bi.booking_id={$booking['id']}");
        $items = [];
        while ($item = $items_q->fetch_assoc()) {
            $item['subtotal_formatted'] = format_rupiah($item['subtotal']);
            $items[] = $item;
        }

        $booking['items'] = $items;
        $booking['visit_date_formatted'] = format_date_id($booking['visit_date']);
        $booking['total_amount_formatted'] = format_rupiah($booking['total_amount']);
        $booking['created_at_formatted'] = date('d M Y H:i', strtotime($booking['created_at']));

        echo json_encode(['status'=>'success','data'=>$booking]);
        $conn->close();
        break;

    case 'cancel_booking':
        if (!is_logged_in()) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
        $code = sanitize($_POST['code'] ?? '');

        $conn = db_connect();
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_code=? AND user_id=? AND status='pending'");
        $stmt->bind_param('si', $code, $_SESSION['user_id']);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        if (!$booking) { echo json_encode(['status'=>'error','message'=>'Pesanan tidak dapat dibatalkan']); $conn->close(); exit; }

        $upd = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE booking_code=?");
        $upd->bind_param('s', $code);
        $upd->execute();
        echo json_encode(['status'=>'success','message'=>'Pesanan berhasil dibatalkan']);
        $conn->close();
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action tidak valid']);
}