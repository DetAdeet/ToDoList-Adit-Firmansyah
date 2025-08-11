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
    
    if ($id <= 0) {
        throw new Exception('ID tugas tidak valid');
    }
    

    $db = getDatabase();
    $pdo = $db->getConnection();
    

    $checkStmt = $pdo->prepare("SELECT nama_tugas, status, prioritas, deadline FROM tasks WHERE id = ?");
    $checkStmt->execute([$id]);
    $task = $checkStmt->fetch();
    
    if (!$task) {
        throw new Exception('Tugas tidak ditemukan');
    }
    
    $taskName = $task['nama_tugas'];
    $taskStatus = $task['status'];
    

    $pdo->beginTransaction();
    
    try {

        $backupStmt = $pdo->prepare("
            INSERT INTO deleted_tasks 
            (original_id, nama_tugas, status, prioritas, tanggal, deadline, deleted_at, deleted_by_ip) 
            SELECT id, nama_tugas, status, prioritas, tanggal, deadline, NOW(), ? 
            FROM tasks WHERE id = ?
        ");
        
        try {
            $backupStmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $id]);
        } catch (PDOException $e) {    
            error_log("Backup failed (table might not exist): " . $e->getMessage());
        }
        
        
        $deleteStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $result = $deleteStmt->execute([$id]);
        
        if ($result && $deleteStmt->rowCount() > 0) {
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = 'Tugas berhasil dihapus!';
            
            
            setSessionMessage('Tugas berhasil dihapus!', 'success');
            
            
            logActivity('DELETE', "Tugas '{$taskName}' (Status: {$taskStatus}) berhasil dihapus");
            
        } else {
            $pdo->rollBack();
            throw new Exception('Gagal menghapus tugas atau tugas tidak ditemukan');
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    $response['errors'][] = 'Terjadi kesalahan pada database';
    
    // Log error
    error_log("Delete Task Error (ID: $id): " . $e->getMessage());
    setSessionMessage('Terjadi kesalahan database saat menghapus tugas', 'error');
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['errors'][] = $e->getMessage();
    
    
    error_log("Delete Task Error (ID: $id): " . $e->getMessage());
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