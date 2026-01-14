<?php
require_once '../config.php';
require_once '../admin_auth.php';

$auth = new AdminAuth();

// Check if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Auchi Projects</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-building"></i>
                    <h1>Auchi<span>Projects</span></h1>
                </div>
                <p>Administrator Login</p>
            </div>
            
            <?php if ($error): ?>
            <div class="login-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Enter username" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Enter password" required>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="login-footer">
                    <p>Default credentials: admin / admin123</p>
                    <a href="../index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </div>
            </form>
        </div>
        
        <div class="login-info">
            <div class="info-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Access</h3>
                <p>Protected admin panel for project management</p>
            </div>
            <div class="info-card">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Geographical Tracking</h3>
                <p>Manage project coordinates and locations</p>
            </div>
            <div class="info-card">
                <i class="fas fa-chart-line"></i>
                <h3>Project Analytics</h3>
                <p>Track progress and status updates</p>
            </div>
        </div>
    </div>

    <script>
        // Add animation to login form
        document.addEventListener('DOMContentLoaded', function() {
            const loginBox = document.querySelector('.login-box');
            loginBox.style.animation = 'slideIn 0.5s ease-out';
            
            // Focus on username field
            document.querySelector('input[name="username"]').focus();
            
            // Show/hide password toggle
            const passwordField = document.querySelector('input[name="password"]');
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            
            toggleBtn.addEventListener('click', function() {
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                this.innerHTML = type === 'password' ? 
                    '<i class="fas fa-eye"></i>' : 
                    '<i class="fas fa-eye-slash"></i>';
            });
            
            // Add toggle button after password field
            passwordField.parentNode.appendChild(toggleBtn);
        });
    </script>
    
    <style>
        /* Login Page Specific Styles */
        .login-body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 40px;
            max-width: 1200px;
            width: 100%;
        }
        
        .login-box {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .login-logo i {
            font-size: 3rem;
            color: var(--secondary);
        }
        
        .login-logo h1 {
            font-size: 2.5rem;
            color: var(--primary);
        }
        
        .login-logo span {
            color: var(--accent);
        }
        
        .login-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .login-error {
            background: #fadbd8;
            color: #943126;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s;
        }
        
        .login-form .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .login-form .form-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .login-form .form-label i {
            color: var(--secondary);
        }
        
        .login-form .form-control {
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .login-form .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--gray);
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--secondary);
        }
        
        .forgot-link {
            color: var(--secondary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--secondary), #2980b9);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.2);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-footer p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .login-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            transition: var(--transition);
        }
        
        .info-card:hover {
            transform: translateX(10px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .info-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: white;
        }
        
        .info-card h3 {
            color: white;
            margin-bottom: 10px;
        }
        
        .info-card p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 40px;
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1.1rem;
        }
        
        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            
            .login-info {
                display: none;
            }
            
            .login-box {
                padding: 30px;
            }
        }
        
        @media (max-width: 480px) {
            .login-box {
                padding: 20px;
            }
            
            .login-logo h1 {
                font-size: 2rem;
            }
            
            .form-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</body>
</html>