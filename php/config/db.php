<?php
class Database {
    private $host = "localhost:3306";
    private $db_name = "parasatp_database";
    private $username = "parasatp_user";
    private $password = "Qwerty_12345";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            // Use error_log instead of echo
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
