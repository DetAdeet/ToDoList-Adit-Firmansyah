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
    $current_status = $_POST['current_status'] ?? '';
    
    if ($id <= 0) {
        throw new Exception('ID tugas tidak valid');
    }
    
    if (!in_array($current_status, ['selesai', 'belum selesai'])) {
        throw new Exception('Status tidak valid');
    }
    
    $new_status = $current_status === 'selesai' ? 'belum selesai' : 'selesai';
    
    $db = getDatabase();
    $pdo = $db->getConnection();
    
    $checkStmt = $pdo->prepare("SELECT nama_tugas, status, deadline FROM tasks WHERE id = ?");
    $checkStmt->execute([$id]);
    $task = $checkStmt->fetch();
    
    if (!$task) {
        throw new Exception('Tugas tidak ditemukan');
    }
    
    if ($task['status'] !== $current_status) {
        throw new Exception('Status tugas sudah berubah, silakan refresh halaman');
    }
    
    $taskName = $task['nama_tugas'];
    $deadline = $task['deadline'];
    
    $updateStmt = $pdo->prepare("
        UPDATE tasks 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$new_status, $id]);
    
    if ($result && $updateStmt->rowCount() > 0) {
        $response['success'] = true;
        $response['new_status'] = $new_status;
        
        if ($new_status === 'selesai') {
            $response['message'] = 'Tugas berhasil ditandai selesai! 🎉';
            setSessionMessage('Tugas berhasil ditandai selesai! 🎉', 'success');
            
            $isOnTime = strtotime($deadline) >= strtotime(date('Y-m-d'));
            $timeStatus = $isOnTime ? 'tepat waktu' : 'terlambat';
            
            logActivity('COMPLETE', "Tugas '{$taskName}' selesai ({$timeStatus})");
            
            if (!$isOnTime) {
                setSessionMessage("Tugas selesai namun melewati deadline ({$deadline})", 'warning');
            }
            
        } else {
            $response['message'] = 'Status tugas berhasil dikembalikan ke belum selesai';
            setSessionMessage('Status tugas berhasil dikembalikan ke belum selesai', 'info');
            
            logActivity('REOPEN', "Tugas '{$taskName}' dibuka kembali");
        }
        
    } else {
        throw new Exception('Gagal mengubah status tugas');
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    $response['errors'][] = 'Terjadi kesalahan pada database';
    
    error_log("Toggle Status Error (ID: $id): " . $e->getMessage());
    setSessionMessage('Terjadi kesalahan database', 'error');
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['errors'][] = $e->getMessage();
    
    error_log("Toggle Status Error (ID: $id): " . $e->getMessage());
    setSessionMessage($e->getMessage(), 'error');
}

redirectBack();

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
    
    if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
        error_log("Failed to create log directory: $log_dir");
        return;
    }
    
    if (file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX) === false) {
        error_log("Failed to write to log file: $log_file");
    }
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>