<?php
require_once 'config/database.php';

class TaskReader {
    private $pdo;
    
    public function __construct() {
        $db = getDatabase();
        $this->pdo = $db->getConnection();
    }
    
    public function getTasks($filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (isset($filters['status']) && $filters['status'] !== '') {
                $where_conditions[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['prioritas']) && $filters['prioritas'] !== '') {
                $where_conditions[] = "prioritas = ?";
                $params[] = $filters['prioritas'];
            }
            
            if (isset($filters['search']) && $filters['search'] !== '') {
                $where_conditions[] = "nama_tugas LIKE ?";
                $params[] = "%{$filters['search']}%";
            }
            
            if (isset($filters['deadline_filter'])) {
                switch ($filters['deadline_filter']) {
                    case 'today':
                        $where_conditions[] = "deadline = CURDATE()";
                        break;
                    case 'tomorrow':
                        $where_conditions[] = "deadline = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
                        break;
                    case 'this_week':
                        $where_conditions[] = "deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                        break;
                    case 'overdue':
                        $where_conditions[] = "deadline < CURDATE() AND status != 'selesai'";
                        break;
                }
            }
            
            $sql = "SELECT *, 
                        CASE 
                            WHEN deadline < CURDATE() AND status != 'selesai' THEN 1 
                            ELSE 0 
                        END as is_overdue
                    FROM tasks";
            
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(" AND ", $where_conditions);
            }
            
            $sort_by = $filters['sort_by'] ?? 'priority_deadline';
            switch ($sort_by) {
                case 'name':
                    $sql .= " ORDER BY nama_tugas ASC";
                    break;
                case 'deadline':
                    $sql .= " ORDER BY deadline ASC";
                    break;
                case 'status':
                    $sql .= " ORDER BY status ASC, deadline ASC";
                    break;
                case 'priority_deadline':
                default:
                    $sql .= " ORDER BY 
                                CASE prioritas 
                                    WHEN 'tinggi' THEN 1 
                                    WHEN 'sedang' THEN 2 
                                    WHEN 'rendah' THEN 3 
                                END ASC, 
                                deadline ASC";
                    break;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $stmt->rowCount()
            ];
            
        } catch (PDOException $e) {
            error_log("Read Tasks Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    public function getTaskById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *, 
                    CASE 
                        WHEN deadline < CURDATE() AND status != 'selesai' THEN 1 
                        ELSE 0 
                    END as is_overdue
                FROM tasks 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                return [
                    'success' => true,
                    'data' => $task
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Tugas tidak ditemukan'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Read Task By ID Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function getTaskStatistics() {
        try {
            $stats = [];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM tasks");
            $stats['total'] = $stmt->fetch()['total'];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as completed FROM tasks WHERE status = 'selesai'");
            $stats['completed'] = $stmt->fetch()['completed'];
            
            $stats['pending'] = $stats['total'] - $stats['completed'];
            
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as overdue 
                FROM tasks 
                WHERE deadline < CURDATE() AND status != 'selesai'
            ");
            $stats['overdue'] = $stmt->fetch()['overdue'];
            
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as today 
                FROM tasks 
                WHERE deadline = CURDATE() AND status != 'selesai'
            ");
            $stats['today'] = $stmt->fetch()['today'];
            
            $stmt = $this->pdo->query("
                SELECT prioritas, COUNT(*) as count 
                FROM tasks 
                WHERE status != 'selesai'
                GROUP BY prioritas
            ");
            $priority_stats = [];
            while ($row = $stmt->fetch()) {
                $priority_stats[$row['prioritas']] = $row['count'];
            }
            $stats['by_priority'] = $priority_stats;
            
            $stats['completion_percentage'] = $stats['total'] > 0 ? 
                round(($stats['completed'] / $stats['total']) * 100, 1) : 0;
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (PDOException $e) {
            error_log("Get Statistics Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function searchTasks($keyword, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *, 
                    CASE 
                        WHEN deadline < CURDATE() AND status != 'selesai' THEN 1 
                        ELSE 0 
                    END as is_overdue
                FROM tasks 
                WHERE nama_tugas LIKE ? 
                ORDER BY 
                    CASE 
                        WHEN nama_tugas LIKE ? THEN 1 
                        ELSE 2 
                    END,
                    CASE prioritas 
                        WHEN 'tinggi' THEN 1 
                        WHEN 'sedang' THEN 2 
                        WHEN 'rendah' THEN 3 
                    END,
                    deadline ASC
                LIMIT ?
            ");
            
            $searchPattern = "%{$keyword}%";
            $exactPattern = "{$keyword}%";
            
            $stmt->execute([$searchPattern, $exactPattern, $limit]);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'keyword' => $keyword
            ];
            
        } catch (PDOException $e) {
            error_log("Search Tasks Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    public function formatTaskForDisplay($task) {
        if (!$task) return null;
        
        $task['formatted_tanggal'] = $this->formatDate($task['tanggal']);
        $task['formatted_deadline'] = $this->formatDate($task['deadline']);
        
        $task['status_badge'] = $task['status'] === 'selesai' ? 
            '✅ Selesai' : '⏳ Belum Selesai';
        
        $priority_icons = [
            'tinggi' => '🔴',
            'sedang' => '🟡',
            'rendah' => '🟢'
        ];
        $task['priority_icon'] = $priority_icons[$task['prioritas']] ?? '⚪';
        
        if ($task['is_overdue'] ?? false) {
            $task['deadline_status'] = 'TERLAMBAT';
            $task['deadline_class'] = 'overdue';
        } elseif ($task['deadline'] === date('Y-m-d')) {
            $task['deadline_status'] = 'HARI INI';
            $task['deadline_class'] = 'today';
        } else {
            $days_left = (strtotime($task['deadline']) - strtotime(date('Y-m-d'))) / 86400;
            if ($days_left <= 3) {
                $task['deadline_status'] = $days_left . ' HARI LAGI';
                $task['deadline_class'] = 'soon';
            } else {
                $task['deadline_status'] = '';
                $task['deadline_class'] = 'normal';
            }
        }
        
        return $task;
    }
    
    private function formatDate($date) {
        $timestamp = strtotime($date);
        return date('d/m/Y', $timestamp);
    }
}

if (basename($_SERVER['PHP_SELF']) === 'read.php') {
    header('Content-Type: application/json');
    
    try {
        $reader = new TaskReader();
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $filters = [
                    'status' => $_GET['status'] ?? '',
                    'prioritas' => $_GET['prioritas'] ?? '',
                    'search' => $_GET['search'] ?? '',
                    'deadline_filter' => $_GET['deadline_filter'] ?? '',
                    'sort_by' => $_GET['sort_by'] ?? 'priority_deadline'
                ];
                $result = $reader->getTasks($filters);
                break;
                
            case 'get':
                $id = intval($_GET['id'] ?? 0);
                $result = $reader->getTaskById($id);
                break;
                
            case 'stats':
                $result = $reader->getTaskStatistics();
                break;
                
            case 'search':
                $keyword = $_GET['q'] ?? '';
                $limit = intval($_GET['limit'] ?? 10);
                $result = $reader->searchTasks($keyword, $limit);
                break;
                
            default:
                $result = [
                    'success' => false,
                    'error' => 'Action tidak valid'
                ];
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>