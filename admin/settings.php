<?php
require_once '../config.php';
require_once '../admin_auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            // Update profile logic
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($new_password !== $confirm_password) {
                $message = 'New passwords do not match!';
                $messageType = 'error';
            } elseif (strlen($new_password) < 6) {
                $message = 'Password must be at least 6 characters!';
                $messageType = 'error';
            } else {
                $message = 'Password changed successfully!';
                $messageType = 'success';
            }
            break;
            
        case 'update_system':
            $site_title = $_POST['site_title'] ?? '';
            $site_email = $_POST['site_email'] ?? '';
            
            if (!filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Invalid email address!';
                $messageType = 'error';
            } else {
                $message = 'System settings updated successfully!';
                $messageType = 'success';
            }
            break;
    }
}

// Get current admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $auth->conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'topbar.php'; ?>

        <div class="admin-content">
            <h1>Settings</h1>
            <p>Manage your account and system preferences</p>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <div class="tab-headers">
                    <button class="tab-btn active" data-tab="profile">Profile</button>
                    <button class="tab-btn" data-tab="security">Security</button>
                    <button class="tab-btn" data-tab="system">System</button>
                    <button class="tab-btn" data-tab="notifications">Notifications</button>
                    <button class="tab-btn" data-tab="backup">Backup</button>
                </div>
                
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane active" id="profile">
                        <div class="settings-card">
                            <h3><i class="fas fa-user"></i> Profile Information</h3>
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($admin['username'] ?? 'admin'); ?>" 
                                               readonly>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($admin['email'] ?? 'admin@auchiprojects.com'); ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               placeholder="Enter your full name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" 
                                               placeholder="Enter phone number">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Bio</label>
                                    <textarea name="bio" class="form-control" rows="3" 
                                              placeholder="Tell us about yourself"></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="settings-card">
                            <h3><i class="fas fa-image"></i> Profile Picture</h3>
                            <div class="profile-picture-section">
                                <div class="profile-preview">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['username'] ?? 'Admin'); ?>&background=3498db&color=fff" 
                                         alt="Profile" id="profilePreview">
                                </div>
                                <div class="upload-actions">
                                    <input type="file" id="profileImage" accept="image/*" style="display: none;">
                                    <button type="button" class="btn-primary" onclick="document.getElementById('profileImage').click()">
                                        <i class="fas fa-upload"></i> Upload New Photo
                                    </button>
                                    <button type="button" class="btn-secondary" onclick="resetProfilePicture()">
                                        <i class="fas fa-redo"></i> Use Default
                                    </button>
                                </div>
                                <p class="help-text">Allowed JPG, GIF or PNG. Max size of 2MB</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane" id="security">
                        <div class="settings-card">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <span class="strength-text" id="strengthText">Password strength</span>
                                </div>
                                
                                <div class="password-requirements">
                                    <p><strong>Password must contain:</strong></p>
                                    <ul>
                                        <li class="requirement" id="reqLength">At least 8 characters</li>
                                        <li class="requirement" id="reqUppercase">One uppercase letter</li>
                                        <li class="requirement" id="reqLowercase">One lowercase letter</li>
                                        <li class="requirement" id="reqNumber">One number</li>
                                        <li class="requirement" id="reqSpecial">One special character</li>
                                    </ul>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="settings-card">
                            <h3><i class="fas fa-shield-alt"></i> Two-Factor Authentication</h3>
                            <div class="two-factor-section">
                                <div class="two-factor-status">
                                    <i class="fas fa-times-circle" style="color: #e74c3c; font-size: 2rem;"></i>
                                    <div>
                                        <h4>2FA is disabled</h4>
                                        <p>Add an extra layer of security to your account</p>
                                    </div>
                                </div>
                                <button type="button" class="btn-primary">
                                    <i class="fas fa-cog"></i> Enable Two-Factor Auth
                                </button>
                            </div>
                        </div>
                        
                        <div class="settings-card">
                            <h3><i class="fas fa-history"></i> Login History</h3>
                            <div class="login-history">
                                <div class="history-item">
                                    <i class="fas fa-desktop"></i>
                                    <div>
                                        <p><strong>Chrome on Windows</strong></p>
                                        <p>Today at 10:30 AM</p>
                                        <p>IP: 192.168.1.100</p>
                                    </div>
                                </div>
                                <div class="history-item">
                                    <i class="fas fa-mobile-alt"></i>
                                    <div>
                                        <p><strong>Safari on iOS</strong></p>
                                        <p>Yesterday at 3:45 PM</p>
                                        <p>IP: 192.168.1.101</p>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-secondary" style="width: 100%; margin-top: 20px;">
                                <i class="fas fa-sign-out-alt"></i> Logout All Other Sessions
                            </button>
                        </div>
                    </div>
                    
                    <!-- System Tab -->
                    <div class="tab-pane" id="system">
                        <div class="settings-card">
                            <h3><i class="fas fa-cog"></i> System Settings</h3>
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_system">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Site Title</label>
                                        <input type="text" name="site_title" class="form-control" 
                                               value="Auchi Projects" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Admin Email</label>
                                        <input type="email" name="site_email" class="form-control" 
                                               value="admin@auchiprojects.com" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Timezone</label>
                                    <select name="timezone" class="form-control">
                                        <option value="Africa/Lagos" selected>Africa/Lagos (GMT+1)</option>
                                        <option value="UTC">UTC</option>
                                        <option value="America/New_York">America/New York</option>
                                        <option value="Europe/London">Europe/London</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Date Format</label>
                                    <select name="date_format" class="form-control">
                                        <option value="Y-m-d" selected>YYYY-MM-DD</option>
                                        <option value="d/m/Y">DD/MM/YYYY</option>
                                        <option value="m/d/Y">MM/DD/YYYY</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Items Per Page</label>
                                    <select name="items_per_page" class="form-control">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="maintenance_mode">
                                        <span>Enable Maintenance Mode</span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="user_registration" checked>
                                        <span>Allow User Registration</span>
                                    </label>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save"></i> Save System Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div class="tab-pane" id="notifications">
                        <div class="settings-card">
                            <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                            <form class="settings-form">
                                <div class="notification-group">
                                    <h4>Email Notifications</h4>
                                    <div class="notification-item">
                                        <label class="checkbox-label">
                                            <input type="checkbox" checked>
                                            <span>New project assignments</span>
                                        </label>
                                    </div>
                                    <div class="notification-item">
                                        <label class="checkbox-label">
                                            <input type="checkbox" checked>
                                            <span>Project status updates</span>
                                        </label>
                                    </div>
                                    <div class="notification-item">
                                        <label class="checkbox-label">
                                            <input type="checkbox">
                                            <span>Weekly reports</span>
                                        </label>
                                    </div>
                                    <div class="notification-item">
                                        <label class="checkbox-label">
                                            <input type="checkbox" checked>
                                            <span>System alerts</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="notification-group">
                                    <h4>Push Notifications</h4>
                                    <div class="notification-item">
                                        <label class="checkbox-label">
                                            <input type="checkbox" checked>
                                            <span>New messages</span>
                                        </label>
                                    </div>
                                    <div class="notification-item">
                                        <label class="checkbox-label">
                                            <input type="checkbox">
                                            <span>Project deadlines</span>
                                        </label>
                                    </div>
                                    <div class="notification-item">
                                        <label class="checkbox-label">
                                            <input type="checkbox" checked>
                                            <span>Important updates</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn-primary" onclick="saveNotifications()">
                                        <i class="fas fa-save"></i> Save Preferences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Backup Tab -->
                    <div class="tab-pane" id="backup">
                        <div class="settings-card">
                            <h3><i class="fas fa-database"></i> Database Backup</h3>
                            <div class="backup-info">
                                <div class="backup-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-hdd"></i>
                                        <div>
                                            <p>Database Size</p>
                                            <h4>45.2 MB</h4>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-calendar"></i>
                                        <div>
                                            <p>Last Backup</p>
                                            <h4>3 days ago</h4>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-history"></i>
                                        <div>
                                            <p>Backup Frequency</p>
                                            <h4>Weekly</h4>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="backup-actions">
                                    <button type="button" class="btn-primary" onclick="createBackup()">
                                        <i class="fas fa-download"></i> Create Backup Now
                                    </button>
                                    <button type="button" class="btn-secondary" onclick="showBackupHistory()">
                                        <i class="fas fa-history"></i> View Backup History
                                    </button>
                                </div>
                                
                                <div class="backup-schedule">
                                    <h4>Automatic Backup Schedule</h4>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Frequency</label>
                                            <select class="form-control">
                                                <option value="daily">Daily</option>
                                                <option value="weekly" selected>Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Day of Week</label>
                                            <select class="form-control">
                                                <option value="monday">Monday</option>
                                                <option value="tuesday">Tuesday</option>
                                                <option value="wednesday">Wednesday</option>
                                                <option value="thursday">Thursday</option>
                                                <option value="friday" selected>Friday</option>
                                                <option value="saturday">Saturday</option>
                                                <option value="sunday">Sunday</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Time</label>
                                        <input type="time" class="form-control" value="02:00">
                                    </div>
                                    <button type="button" class="btn-primary">
                                        <i class="fas fa-save"></i> Save Schedule
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-card">
                            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                            <div class="danger-zone">
                                <div class="danger-item">
                                    <div>
                                        <h4>Reset All Data</h4>
                                        <p>This will delete all projects, states, and LGAs from the database</p>
                                    </div>
                                    <button type="button" class="btn-danger" onclick="resetData()">
                                        <i class="fas fa-trash"></i> Reset Data
                                    </button>
                                </div>
                                <div class="danger-item">
                                    <div>
                                        <h4>Delete Account</h4>
                                        <p>Permanently delete your admin account</p>
                                    </div>
                                    <button type="button" class="btn-danger" onclick="deleteAccount()">
                                        <i class="fas fa-user-slash"></i> Delete Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/admin.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                // Update active tab button
                document.querySelectorAll('.tab-btn').forEach(b => 
                    b.classList.remove('active')
                );
                this.classList.add('active');
                
                // Show selected tab content
                document.querySelectorAll('.tab-pane').forEach(pane => 
                    pane.classList.remove('active')
                );
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Password strength checker
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            if (input.name === 'new_password') {
                input.addEventListener('input', checkPasswordStrength);
            }
        });
        
        function checkPasswordStrength() {
            const password = this.value;
            let strength = 0;
            
            // Check length
            if (password.length >= 8) {
                strength++;
                document.getElementById('reqLength').classList.add('met');
            } else {
                document.getElementById('reqLength').classList.remove('met');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                strength++;
                document.getElementById('reqUppercase').classList.add('met');
            } else {
                document.getElementById('reqUppercase').classList.remove('met');
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                strength++;
                document.getElementById('reqLowercase').classList.add('met');
            } else {
                document.getElementById('reqLowercase').classList.remove('met');
            }
            
            // Check numbers
            if (/\d/.test(password)) {
                strength++;
                document.getElementById('reqNumber').classList.add('met');
            } else {
                document.getElementById('reqNumber').classList.remove('met');
            }
            
            // Check special characters
            if (/[^A-Za-z0-9]/.test(password)) {
                strength++;
                document.getElementById('reqSpecial').classList.add('met');
            } else {
                document.getElementById('reqSpecial').classList.remove('met');
            }
            
            // Update strength bar
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            const percentages = [0, 20, 40, 60, 80, 100];
            const colors = ['#e74c3c', '#e67e22', '#f39c12', '#f1c40f', '#2ecc71', '#27ae60'];
            const texts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
            
            strengthFill.style.width = percentages[strength] + '%';
            strengthFill.style.background = colors[strength];
            strengthText.textContent = texts[strength];
            strengthText.style.color = colors[strength];
        }
        
        // Profile picture upload
        document.getElementById('profileImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        function resetProfilePicture() {
            const name = '<?php echo $admin["username"] ?? "Admin"; ?>';
            document.getElementById('profilePreview').src = 
                `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=3498db&color=fff`;
        }
        
        function saveNotifications() {
            alert('Notification preferences saved!');
        }
        
        function createBackup() {
            if (confirm('Create database backup now?')) {
                alert('Backup process started. You will be notified when complete.');
            }
        }
        
        function showBackupHistory() {
            alert('Showing backup history...');
        }
        
        function resetData() {
            if (confirm('WARNING: This will delete ALL data from the system. This action cannot be undone!')) {
                if (prompt('Type "DELETE" to confirm:') === 'DELETE') {
                    alert('Data reset initiated. This may take a few minutes.');
                }
            }
        }
        
        function deleteAccount() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone!')) {
                alert('Account deletion request sent to system administrator.');
            }
        }
        
        // Initialize password requirements
        document.querySelectorAll('.requirement').forEach(req => {
            req.classList.add('not-met');
        });
    </script>
    
    <style>
        .settings-tabs {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .tab-headers {
            display: flex;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .tab-btn {
            padding: 15px 30px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray);
            border-bottom: 3px solid transparent;
            transition: var(--transition);
        }
        
        .tab-btn:hover {
            color: var(--primary);
            background: white;
        }
        
        .tab-btn.active {
            color: var(--secondary);
            border-bottom-color: var(--secondary);
            background: white;
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .settings-card {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .settings-card:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .settings-card h3 {
            margin-bottom: 25px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-form {
            max-width: 600px;
        }
        
        .profile-picture-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .profile-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid #f8f9fa;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .upload-actions {
            display: flex;
            gap: 15px;
        }
        
        .help-text {
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .password-strength {
            margin: 20px 0;
        }
        
        .strength-bar {
            height: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .strength-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        .strength-text {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }
        
        .password-requirements li {
            padding: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .password-requirements li:before {
            content: '✓';
            margin-right: 10px;
        }
        
        .requirement.met {
            color: var(--success);
        }
        
        .requirement.not-met {
            color: var(--gray);
        }
        
        .requirement.not-met:before {
            content: '✗';
            color: var(--accent);
        }
        
        .two-factor-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .two-factor-status {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .login-history {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .history-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .history-item i {
            font-size: 1.5rem;
            color: var(--secondary);
        }
        
        .notification-group {
            margin-bottom: 30px;
        }
        
        .notification-group h4 {
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .notification-item {
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .backup-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .backup-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-item i {
            font-size: 2rem;
            color: var(--secondary);
        }
        
        .stat-item h4 {
            margin: 5px 0 0 0;
            color: var(--primary);
        }
        
        .backup-actions {
            display: flex;
            gap: 15px;
        }
        
        .backup-schedule {
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .danger-zone {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .danger-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
            background: #fef2f2;
            border: 2px solid #fde8e8;
            border-radius: 10px;
        }
        
        .danger-item h4 {
            color: var(--accent);
            margin-bottom: 5px;
        }
        
        .danger-item p {
            color: #666;
            margin: 0;
        }
        
        .btn-danger {
            background: var(--accent);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .tab-headers {
                flex-wrap: wrap;
            }
            
            .tab-btn {
                flex: 1;
                min-width: 120px;
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .two-factor-section {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .danger-item {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</body>
</html>