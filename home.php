<?php
session_start();

require_once 'config/database.php';
require_once 'actions/read.php';

$errors = [];
$success = '';

function getSessionMessages() {
    $messages = $_SESSION['messages'] ?? [];
    unset($_SESSION['messages']);
    return $messages;
}

$messages = getSessionMessages();

try {
    $taskReader = new TaskReader();
    
    $filters = [
        'status' => $_GET['status'] ?? '',
        'prioritas' => $_GET['prioritas'] ?? '',
        'search' => $_GET['search'] ?? '',
        'deadline_filter' => $_GET['deadline_filter'] ?? '',
        'sort_by' => $_GET['sort_by'] ?? 'priority_deadline'
    ];
    
    $tasksResult = $taskReader->getTasks($filters);
    $statsResult = $taskReader->getTaskStatistics();
    
    $tasks = $tasksResult['success'] ? $tasksResult['data'] : [];
    $stats = $statsResult['success'] ? $statsResult['data'] : [];
    
    if (!$tasksResult['success']) {
        $errors[] = $tasksResult['error'];
    }
    
} catch (Exception $e) {
    $errors[] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
    $tasks = [];
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To Do List</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📝</text></svg>">
</head>
<body>
    <div class="container">
        <div class="header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
    <div>
        <h1>📝 To Do List</h1>
        <p>Kelola tugas Anda dengan mudah dan efisien</p>
    </div>
    
    <form method="POST" action="logout.php">
        <button type="submit" class="btn btn-danger" style="margin-top: 10px;">
            🔓 Logout
        </button>
    </form>
</div>

        <div class="content">
                <?php foreach ($messages as $message): ?>
                <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                    <strong>
                        <?php echo $message['type'] === 'error' ? '❌ Error:' : ($message['type'] === 'success' ? '✅ Berhasil:' : '💡 Info:'); ?>
                    </strong>
                    <?php echo htmlspecialchars($message['text']); ?>
                </div>
            <?php endforeach; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <strong>❌ Error:</strong>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($stats)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="total-tasks" style="color: #667eea;">
                        <?php echo $stats['total']; ?>
                    </div>
                    <div class="stat-label">Total Tugas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="completed-tasks" style="color: #28a745;">
                        <?php echo $stats['completed']; ?>
                    </div>
                    <div class="stat-label">Selesai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="pending-tasks" style="color: #ffc107;">
                        <?php echo $stats['pending']; ?>
                    </div>
                    <div class="stat-label">Belum Selesai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="overdue-tasks" style="color: #dc3545;">
                        <?php echo $stats['overdue']; ?>
                    </div>
                    <div class="stat-label">Terlambat</div>
                </div>
            </div>
            
            <div style="margin-bottom: 30px;">
                <div style="background: #e9ecef; border-radius: 10px; height: 20px; overflow: hidden;">
                    <div id="progress-bar" style="
                        background: linear-gradient(90deg, #28a745, #20c997); 
                        height: 100%; 
                        border-radius: 10px; 
                        width: <?php echo $stats['completion_percentage']; ?>%;
                        transition: width 0.5s ease;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 0.8rem;
                        font-weight: 600;
                    ">
                        <?php echo $stats['completion_percentage']; ?>%
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-section">
                <h2>➕ Tambah Tugas Baru</h2>
                <form method="POST" action="actions/create.php" id="add-task-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nama_tugas">Nama Tugas *</label>
                            <input type="text" id="nama_tugas" name="nama_tugas" required 
                                   placeholder="Masukkan nama tugas...">
                        </div>
                        
                        <div class="form-group">
                            <label for="prioritas">Prioritas *</label>
                            <select id="prioritas" name="prioritas" required>
                                <option value="">Pilih Prioritas</option>
                                <option value="tinggi">🔴 Tinggi</option>
                                <option value="sedang">🟡 Sedang</option>
                                <option value="rendah">🟢 Rendah</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tanggal">Tanggal Dibuat *</label>
                            <input type="date" id="tanggal" name="tanggal" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="deadline">Deadline *</label>
                            <input type="date" id="deadline" name="deadline" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        ➕ Tambah Tugas
                    </button>
                </form>
            </div>
            
            <div class="form-section">
                <h2>🔍 Filter & Pencarian</h2>
                <form method="GET" action="" id="filter-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="search">Cari Tugas</label>
                            <input type="text" id="search" name="search" placeholder="Ketik nama tugas..."
                                   value="<?php echo htmlspecialchars($filters['search']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Filter Status</label>
                            <select id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="selesai" <?php echo $filters['status'] === 'selesai' ? 'selected' : ''; ?>>✅ Selesai</option>
                                <option value="belum selesai" <?php echo $filters['status'] === 'belum selesai' ? 'selected' : ''; ?>>⏳ Belum Selesai</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="prioritas">Filter Prioritas</label>
                            <select id="prioritas" name="prioritas">
                                <option value="">Semua Prioritas</option>
                                <option value="tinggi" <?php echo $filters['prioritas'] === 'tinggi' ? 'selected' : ''; ?>>🔴 Tinggi</option>
                                <option value="sedang" <?php echo $filters['prioritas'] === 'sedang' ? 'selected' : ''; ?>>🟡 Sedang</option>
                                <option value="rendah" <?php echo $filters['prioritas'] === 'rendah' ? 'selected' : ''; ?>>🟢 Rendah</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sort_by">Urutkan Berdasarkan</label>
                            <select id="sort_by" name="sort_by">
                                <option value="priority_deadline" <?php echo $filters['sort_by'] === 'priority_deadline' ? 'selected' : ''; ?>>Prioritas & Deadline</option>
                                <option value="deadline" <?php echo $filters['sort_by'] === 'deadline' ? 'selected' : ''; ?>>Deadline</option>
                                <option value="name" <?php echo $filters['sort_by'] === 'name' ? 'selected' : ''; ?>>Nama Tugas</option>
                                <option value="status" <?php echo $filters['sort_by'] === 'status' ? 'selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-info btn-small">
                            🔍 Filter
                        </button>
                        <a href="home.php" class="btn btn-secondary btn-small">
                            🔄 Reset Filter
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="tasks-section">
                <h2>
                    📋 Daftar Tugas 
                    <span style="font-size: 1rem; color: #6c757d; font-weight: normal;">
                        (<?php echo count($tasks); ?> tugas)
                    </span>
                </h2>
                
                <?php if (empty($tasks)): ?>
                    <div class="no-tasks">
                        <div class="icon">📭</div>
                        <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['prioritas'])): ?>
                            Tidak ada tugas yang sesuai dengan filter.
                            <br><a href="home.php" style="color: #4facfe;">Reset filter</a> untuk melihat semua tugas.
                        <?php else: ?>
                            Belum ada tugas. Tambahkan tugas pertama Anda!
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <?php $formattedTask = $taskReader->formatTaskForDisplay($task); ?>
                        <div class="task-item <?php echo $task['status'] === 'selesai' ? 'completed' : ''; ?> <?php echo isset($task['is_overdue']) && $task['is_overdue'] ? 'overdue' : ''; ?>" 
                             data-task-id="<?php echo $task['id']; ?>">
                            
                            <div class="task-header">
                                <div class="task-title <?php echo $task['status'] === 'selesai' ? 'completed' : ''; ?>">
                                    <?php echo htmlspecialchars($task['nama_tugas']); ?>
                                </div>
                            </div>
                            
                            <div class="task-details">
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="status-badge status-<?php echo $task['status'] === 'selesai' ? 'selesai' : 'belum'; ?>">
                                        <?php echo $formattedTask['status_badge']; ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Prioritas</span>
                                    <span class="priority priority-<?php echo $task['prioritas']; ?>">
                                        <?php echo $formattedTask['priority_icon'] . ' ' . ucfirst($task['prioritas']); ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Tanggal Dibuat</span>
                                    <span class="detail-value">
                                        📅 <?php echo $formattedTask['formatted_tanggal']; ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Deadline</span>
                                    <span class="detail-value">
                                        ⏰ <?php echo $formattedTask['formatted_deadline']; ?>
                                        <?php if ($formattedTask['deadline_status']): ?>
                                            <span style="color: <?php echo $formattedTask['deadline_class'] === 'overdue' ? '#dc3545' : '#ef6c00'; ?>; font-weight: bold;">
                                                (<?php echo $formattedTask['deadline_status']; ?>)
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="task-actions">
                                <form method="POST" action="actions/toggle_status.php" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $task['status']; ?>">
                                    <button type="submit" 
                                            class="btn btn-small <?php echo $task['status'] === 'selesai' ? 'btn-warning' : 'btn-success'; ?>">
                                        <?php echo $task['status'] === 'selesai' ? '↩️ Batal Selesai' : '✅ Tandai Selesai'; ?>
                                    </button>
                                </form>
                                
                                <button onclick="toggleEdit(<?php echo $task['id']; ?>)" 
                                        class="btn btn-small btn-info">
                                    ✏️ Edit
                                </button>
                                
                                <form method="POST" action="actions/delete.php" style="display: inline;" 
                                      data-confirm="delete">
                                    <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </div>
                            
                            <div id="edit-form-<?php echo $task['id']; ?>" class="edit-form d-none">
                                <form method="POST" action="actions/edit.php">
                                    <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Nama Tugas</label>
                                            <input type="text" name="nama_tugas" 
                                                   value="<?php echo htmlspecialchars($task['nama_tugas']); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Prioritas</label>
                                            <select name="prioritas" required>
                                                <option value="tinggi" <?php echo $task['prioritas'] === 'tinggi' ? 'selected' : ''; ?>>🔴 Tinggi</option>
                                                <option value="sedang" <?php echo $task['prioritas'] === 'sedang' ? 'selected' : ''; ?>>🟡 Sedang</option>
                                                <option value="rendah" <?php echo $task['prioritas'] === 'rendah' ? 'selected' : ''; ?>>🟢 Rendah</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Tanggal</label>
                                            <input type="date" name="tanggal" 
                                                   value="<?php echo $task['tanggal']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Deadline</label>
                                            <input type="date" name="deadline" 
                                                   value="<?php echo $task['deadline']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-small btn-primary">
                                            💾 Simpan
                                        </button>
                                        <button type="button" onclick="toggleEdit(<?php echo $task['id']; ?>)" 
                                                class="btn btn-small btn-secondary">
                                            ❌ Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; padding: 20px; color:rgb(255, 255, 255); font-size: 0.9rem;">
        <p>© <?php echo date('Y'); ?> To Do List  | 
           <a href="#" onclick="showHelp()" style="color: #4facfe; text-decoration: none;">📖 Bantuan</a> |
           <a href="#" onclick="showShortcuts()" style="color: #4facfe; text-decoration: none;">⌨️ Shortcut</a>
        </p>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        function showHelp() {
            const helpContent = `
                <h3>📖 Panduan Penggunaan</h3>
                <div style="text-align: left; line-height: 1.6;">
                    <p><strong>Menambah Tugas:</strong></p>
                    <ul>
                        <li>Isi form "Tambah Tugas Baru"</li>
                        <li>Semua field bertanda (*) wajib diisi</li>
                        <li>Deadline tidak boleh kurang dari hari ini</li>
                    </ul>
                    
                    <p><strong>Mengelola Tugas:</strong></p>
                    <ul>
                        <li>✅ Tandai Selesai: Ubah status tugas</li>
                        <li>✏️ Edit: Ubah detail tugas</li>
                        <li>🗑️ Hapus: Hapus tugas permanen</li>
                    </ul>
                    
                    <p><strong>Filter & Pencarian:</strong></p>
                    <ul>
                        <li>Gunakan kotak pencarian untuk cari tugas</li>
                        <li>Filter berdasarkan status dan prioritas</li>
                        <li>Urutkan sesuai kebutuhan</li>
                    </ul>
                </div>
            `;
            
            window.todoApp.showConfirmDialog('Bantuan', helpContent, 'info');
        }
        
        function showShortcuts() {
            const shortcutsContent = `
                <h3>⌨️ Keyboard Shortcuts</h3>
                <div style="text-align: left; line-height: 1.6;">
                    <ul>
                        <li><strong>Ctrl + N:</strong> Focus ke input tugas baru</li>
                        <li><strong>Ctrl + F:</strong> Focus ke kotak pencarian</li>
                        <li><strong>Escape:</strong> Tutup semua form edit</li>
                    </ul>
                </div>
            `;
            
            window.todoApp.showConfirmDialog('Keyboard Shortcuts', shortcutsContent, 'info');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const filterInputs = document.querySelectorAll('#filter-form input, #filter-form select');
            filterInputs.forEach(input => {
                if (input.type === 'text') {
                    let timeout;
                    input.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => {
                            document.getElementById('filter-form').submit();
                        }, 500);
                    });
                } else {
                    input.addEventListener('change', function() {
                        document.getElementById('filter-form').submit();
                    });
                }
            });
        });
    </script>
</body>
</html>