<?php

session_start();
require_once '../config/database.php';

$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan');
    }
    
    $nama_tugas = trim($_POST['nama_tugas'] ?? '');
    $prioritas = $_POST['prioritas'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    
    $errors = [];
    
    if (empty($nama_tugas)) {
        $errors[] = 'Nama tugas tidak boleh kosong';
    } elseif (strlen($nama_tugas) > 255) {
        $errors[] = 'Nama tugas maksimal 255 karakter';
    }
    
    if (empty($prioritas)) {
        $errors[] = 'Prioritas harus dipilih';
    } elseif (!in_array($prioritas, ['tinggi', 'sedang', 'rendah'])) {
        $errors[] = 'Prioritas tidak valid';
    }
    
    if (empty($tanggal)) {
        $errors[] = 'Tanggal harus diisi';
    } elseif (!validateDate($tanggal)) {
        $errors[] = 'Format tanggal tidak valid';
    }
    
    if (empty($deadline)) {
        $errors[] = 'Deadline harus diisi';
    } elseif (!validateDate($deadline)) {
        $errors[] = 'Format deadline tidak valid';
    } elseif (strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Deadline tidak boleh kurang dari hari ini';
    }
    
    if (empty($errors)) {
        $db = getDatabase();
        $pdo = $db->getConnection();
        
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE nama_tugas = ? AND status != 'selesai'");
        $checkStmt->execute([$nama_tugas]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $errors[] = 'Tugas dengan nama yang sama sudah ada dan belum selesai';
        }
    }
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Data tidak valid';
        setSessionMessage($errors, 'error');
        redirectBack();
        exit;
    }
    
    $db = getDatabase();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO tasks (nama_tugas, status, prioritas, tanggal, deadline, created_at) 
        VALUES (?, 'belum selesai', ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([$nama_tugas, $prioritas, $tanggal, $deadline]);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Tugas berhasil ditambahkan!';
        $response['task_id'] = $pdo->lastInsertId();
    
        setSessionMessage('Tugas berhasil ditambahkan!', 'success');
        
        logActivity('CREATE', "Tugas '{$nama_tugas}' berhasil dibuat");
        
    } else {
        throw new Exception('Gagal menyimpan tugas ke database');
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    $response['errors'][] = 'Terjadi kesalahan pada database';
    
    error_log("Create Task Error: " . $e->getMessage());
    setSessionMessage('Terjadi kesalahan database', 'error');
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['errors'][] = $e->getMessage();
    
    error_log("Create Task Error: " . $e->getMessage());
    setSessionMessage($e->getMessage(), 'error');
}

redirectBack();

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function setSessionMessage($message, $type = 'info') {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    
    $_SESSION['messages'][] = [
        'text' => $message,
        'type' => $type,
        'time' => time()
    ];
}

function redirectBack() {
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? '../home.php';
    
    $parsed_url = parse_url($redirect_url);
    $current_host = $_SERVER['HTTP_HOST'];
    
    if (isset($parsed_url['host']) && $parsed_url['host'] !== $current_host) {
        $redirect_url = '../home.php';
    }
    
    header("Location: $redirect_url");
    exit;
}

function logActivity($action, $description) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'description' => $description,
        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    

    $log_file = '../logs/activity.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>