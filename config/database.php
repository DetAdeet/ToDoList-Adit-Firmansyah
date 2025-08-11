<?php

class Database {
    private $host = 'localhost';
    private $dbname = 'todolist_db';
    private $username = 'root';
    private $password = '';
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            $this->handleError("Koneksi database gagal: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    private function handleError($message) {
        error_log($message);
        
        die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Error</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 50px; }
                .error-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; text-align: center; }
                .error-title { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
                .error-message { color: #6c757d; line-height: 1.6; }
                .retry-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='error-title'>❌ Database Connection Error</div>
                <div class='error-message'>
                    Maaf, terjadi kesalahan saat menghubungkan ke database.<br>
                    Silakan periksa konfigurasi database atau coba lagi nanti.
                    <br><br>
                    <small>Error: " . htmlspecialchars($message) . "</small>
                </div>
                <button class='retry-btn' onclick='window.location.reload()'>🔄 Coba Lagi</button>
            </div>
        </body>
        </html>");
    }
}

function getDatabase() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database;
}
?>