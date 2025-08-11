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
    
    $id = intval($_POST['id'] ?? 0);
    $nama_tugas = trim($_POST['nama_tugas'] ?? '');
    $prioritas = $_POST['prioritas'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    
    $errors = [];
    
    if ($id <= 0) {
        $errors[] = 'ID tugas tidak valid';
    }
    
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
    
    $checkStmt = $pdo->prepare("SELECT nama_tugas, status FROM tasks WHERE id = ?");
    $checkStmt->execute([$id]);
    $existingTask = $checkStmt->fetch();
    
    if (!$existingTask) {
        throw new Exception('Tugas tidak ditemukan');
    }
    
    $duplicateStmt = $pdo->prepare("
        SELECT COUNT(*) FROM tasks 
        WHERE nama_tugas = ? AND id != ? AND status != 'selesai'
    ");
    $duplicateStmt->execute([$nama_tugas, $id]);
    
    if ($duplicateStmt->fetchColumn() > 0) {
        $errors[] = 'Tugas dengan nama yang sama sudah ada dan belum selesai';
        setSessionMessage($errors, 'error');
        redirectBack();
        exit;
    }
    
    $updateStmt = $pdo->prepare("
        UPDATE tasks 
        SET nama_tugas = ?, prioritas = ?, tanggal = ?, deadline = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$nama_tugas, $prioritas, $tanggal, $deadline, $id]);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Tugas berhasil diupdate!';
        
        setSessionMessage('Tugas berhasil diupdate!', 'success');
        
        $oldName = $existingTask['nama_tugas'];
        $logMessage = $oldName !== $nama_tugas ? 
            "Tugas '{$oldName}' diupdate menjadi '{$nama_tugas}'" : 
            "Tugas '{$nama_tugas}' diupdate";
        
        logActivity('UPDATE', $logMessage);
        
        $changes = [];
        if ($oldName !== $nama_tugas) {
            $changes[] = "Nama: '{$oldName}' → '{$nama_tugas}'";
        }
        
        if (!empty($changes)) {
            logActivity('UPDATE_DETAIL', 'Perubahan: ' . implode(', ', $changes));
        }
        
    } else {
        throw new Exception('Gagal mengupdate tugas');
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    $response['errors'][] = 'Terjadi kesalahan pada database';
    
    error_log("Edit Task Error (ID: $id): " . $e->getMessage());
    setSessionMessage('Terjadi kesalahan database', 'error');
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['errors'][] = $e->getMessage();
    
    error_log("Edit Task Error (ID: $id): " . $e->getMessage());
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
    
    if (is_array($message)) {
        foreach ($message as $msg) {
            $_SESSION['messages'][] = [
                'text' => $msg,
                'type' => $type,
                'time' => time()
            ];
        }
    } else {
        $_SESSION['messages'][] = [
            'text' => $message,
            'type' => $type,
            'time' => time()
        ];
    }
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