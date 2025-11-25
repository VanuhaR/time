<?php
// --- filename: src/Config/Database.php ---
class Database {
    private $host;
    private $db;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    public $pdo;

    public function __construct() {
        $dotenv = parse_ini_file(__DIR__ . '/../../.env');
        $this->host = $dotenv['DB_HOST'] ?? 'localhost';
        $this->db = $dotenv['DB_NAME'] ?? 'time_tracking';
        $this->username = $dotenv['DB_USER'] ?? 'root';
        $this->password = $dotenv['DB_PASS'] ?? '';

        $this->connect();
    }

    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log('DB Connection Error: ' . $e->getMessage());
            http_response_code(500);
            die('Сервис временно недоступен.');
        }
    }
}
