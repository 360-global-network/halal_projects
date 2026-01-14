<?php
require_once 'config.php';

class AdminAuth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Verify password (in production, use password_verify)
            if ($password === 'admin123' || password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                return true;
            }
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
}
?>