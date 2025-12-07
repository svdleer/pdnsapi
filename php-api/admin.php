<?php
/**
 * Admin Dashboard for API Key & IP Management
 * Simple admin interface for managing API keys and IP allowlist
 */

// Determine the correct base path
$base_path = realpath(__DIR__);

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';

// Check if user is authenticated (simple password protection)
session_start();

$admin_password = $_ENV['ADMIN_PANEL_PASSWORD'] ?? 'admin123'; // Change this!

if (!isset($_SESSION['admin_authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $admin_password) {
            $_SESSION['admin_authenticated'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $login_error = 'Invalid password';
        }
    }
    
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - PowerDNS API</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-container {
                background: white;
                padding: 3rem;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 400px;
            }
            h1 {
                color: #333;
                margin-bottom: 0.5rem;
                font-size: 1.8rem;
            }
            p {
                color: #666;
                margin-bottom: 2rem;
            }
            .form-group {
                margin-bottom: 1.5rem;
            }
            label {
                display: block;
                margin-bottom: 0.5rem;
                color: #333;
                font-weight: 500;
            }
            input[type="password"] {
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #e0e0e0;
                border-radius: 6px;
                font-size: 1rem;
                transition: border-color 0.3s;
            }
            input[type="password"]:focus {
                outline: none;
                border-color: #667eea;
            }
            button {
                width: 100%;
                padding: 0.75rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }
            button:hover {
                transform: translateY(-2px);
            }
            .error {
                background: #fee;
                color: #c33;
                padding: 0.75rem;
                border-radius: 6px;
                margin-bottom: 1rem;
                border-left: 4px solid #c33;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1>🔐 Admin Login</h1>
            <p>PowerDNS API Administration</p>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Logout handler
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Handle API operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_api_key':
                $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ?");
                $stmt->execute([$_POST['key_id']]);
                $message = 'API key deleted successfully';
                $message_type = 'success';
                break;
                
            case 'toggle_api_key':
                $stmt = $db->prepare("UPDATE api_keys SET enabled = NOT enabled WHERE id = ?");
                $stmt->execute([$_POST['key_id']]);
                $message = 'API key status updated';
                $message_type = 'success';
                break;
                
            case 'delete_ip':
                $stmt = $db->prepare("DELETE FROM ip_allowlist WHERE id = ?");
                $stmt->execute([$_POST['ip_id']]);
                $message = 'IP address removed from allowlist';
                $message_type = 'success';
                break;
                
            case 'add_ip':
                $ip = trim($_POST['ip_address']);
                $description = trim($_POST['description']);
                
                // Validate IP
                if (strpos($ip, '/') !== false) {
                    list($subnet, $mask) = explode('/', $ip);
                    if (!filter_var($subnet, FILTER_VALIDATE_IP) || !is_numeric($mask)) {
                        $message = 'Invalid IP/CIDR format';
                        $message_type = 'error';
                        break;
                    }
                } else {
                    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                        $message = 'Invalid IP address';
                        $message_type = 'error';
                        break;
                    }
                }
                
                $stmt = $db->prepare("INSERT INTO ip_allowlist (ip_address, description, enabled) VALUES (?, ?, 1)");
                $stmt->execute([$ip, $description]);
                $message = 'IP address added to allowlist';
                $message_type = 'success';
                break;
                
            case 'toggle_ip':
                $stmt = $db->prepare("UPDATE ip_allowlist SET enabled = NOT enabled WHERE id = ?");
                $stmt->execute([$_POST['ip_id']]);
                $message = 'IP status updated';
                $message_type = 'success';
                break;
                
            case 'edit_ip_description':
                $description = trim($_POST['description']);
                $stmt = $db->prepare("UPDATE ip_allowlist SET description = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$description, $_POST['ip_id']]);
                $message = 'IP description updated successfully';
                $message_type = 'success';
                break;
        }
    }
}

// Fetch data
$api_keys = $db->query("
    SELECT k.*, a.username as account_name 
    FROM api_keys k 
    LEFT JOIN accounts a ON k.account_id = a.id 
    ORDER BY k.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$ip_allowlist = $db->query("SELECT * FROM ip_allowlist ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total_keys' => $db->query("SELECT COUNT(*) FROM api_keys")->fetchColumn(),
    'active_keys' => $db->query("SELECT COUNT(*) FROM api_keys WHERE enabled = 1")->fetchColumn(),
    'total_ips' => $db->query("SELECT COUNT(*) FROM ip_allowlist")->fetchColumn(),
    'active_ips' => $db->query("SELECT COUNT(*) FROM ip_allowlist WHERE enabled = 1")->fetchColumn(),
    'total_accounts' => $db->query("SELECT COUNT(*) FROM accounts")->fetchColumn(),
    'total_domains' => $db->query("SELECT COUNT(*) FROM domains")->fetchColumn(),
];

// Get recent activity
$recent_keys = $db->query("
    SELECT k.id, k.description, k.last_used_at, a.username as account_name
    FROM api_keys k
    LEFT JOIN accounts a ON k.account_id = a.id
    WHERE k.last_used_at IS NOT NULL
    ORDER BY k.last_used_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PowerDNS API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .section-header {
            padding: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            font-size: 1.3rem;
            color: #333;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-danger {
            background: #f56565;
            color: white;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #555;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        input[type="text"] {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .code {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .ip-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .key-preview {
            font-family: 'Courier New', monospace;
            color: #667eea;
            font-weight: 600;
        }
        
        .edit-form {
            display: none;
        }
        
        .edit-form.active {
            display: block;
        }
        
        .edit-description-input {
            width: 100%;
            padding: 0.5rem;
            border: 2px solid #667eea;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .btn-success {
            background: #48bb78;
            color: white;
        }
        
        .btn-success:hover {
            background: #38a169;
        }
        
        .btn-secondary {
            background: #a0aec0;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #718096;
        }
        
        .recent-activity {
            padding: 1.5rem;
        }
        
        .activity-item {
            padding: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            color: #999;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🛡️ PowerDNS API - Admin Dashboard</h1>
            <a href="?logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message message-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['active_keys']; ?></div>
                <div class="stat-label">Active API Keys</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['active_ips']; ?></div>
                <div class="stat-label">Active IPs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_accounts']; ?></div>
                <div class="stat-label">Accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_domains']; ?></div>
                <div class="stat-label">Domains</div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <?php if (!empty($recent_keys)): ?>
        <div class="section">
            <div class="section-header">
                <h2>📊 Recent API Key Activity</h2>
            </div>
            <div class="recent-activity">
                <?php foreach ($recent_keys as $key): ?>
                    <div class="activity-item">
                        <div>
                            <strong><?php echo htmlspecialchars($key['description']); ?></strong>
                            <span class="badge badge-info"><?php echo htmlspecialchars($key['account_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="activity-time">
                            Last used: <?php echo htmlspecialchars($key['last_used_at']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- API Keys Section -->
        <div class="section">
            <div class="section-header">
                <h2>🔑 API Keys</h2>
                <a href="docs.html" class="btn btn-primary" target="_blank">API Documentation</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Key Preview</th>
                        <th>Account</th>
                        <th>Description</th>
                        <th>IP Restrictions</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($api_keys as $key): 
                        $allowed_ips = json_decode($key['allowed_ips'], true);
                    ?>
                        <tr>
                            <td><?php echo $key['id']; ?></td>
                            <td><span class="key-preview"><?php echo substr($key['api_key'], 0, 20); ?>...</span></td>
                            <td><?php echo htmlspecialchars($key['account_name'] ?? 'Admin'); ?></td>
                            <td><?php echo htmlspecialchars($key['description']); ?></td>
                            <td>
                                <?php if ($allowed_ips): ?>
                                    <span class="badge badge-info"><?php echo count($allowed_ips); ?> IPs</span>
                                <?php else: ?>
                                    <span class="badge badge-success">No restriction</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($key['enabled']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $key['last_used_at'] ? htmlspecialchars($key['last_used_at']) : 'Never'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_api_key">
                                        <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-small">
                                            <?php echo $key['enabled'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this API key?');">
                                        <input type="hidden" name="action" value="delete_api_key">
                                        <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- IP Allowlist Section -->
        <div class="section">
            <div class="section-header">
                <h2>🌐 IP Allowlist</h2>
            </div>
            
            <!-- Add IP Form -->
            <form method="POST">
                <input type="hidden" name="action" value="add_ip">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ip_address">IP Address / CIDR</label>
                        <input type="text" id="ip_address" name="ip_address" placeholder="192.168.1.100 or 10.0.0.0/8" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" placeholder="Office network" required>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Add IP</button>
                    </div>
                </div>
            </form>
            
            <!-- IP List -->
            <div class="ip-list">
                <table>
                    <thead>
                        <tr>
                            <th>IP Address / CIDR</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ip_allowlist as $ip): ?>
                            <tr id="ip-row-<?php echo $ip['id']; ?>">
                                <td><span class="code"><?php echo htmlspecialchars($ip['ip_address']); ?></span></td>
                                <td>
                                    <div class="description-view" id="desc-view-<?php echo $ip['id']; ?>">
                                        <?php echo htmlspecialchars($ip['description']); ?>
                                    </div>
                                    <div class="edit-form" id="desc-edit-<?php echo $ip['id']; ?>">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="edit_ip_description">
                                            <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                            <input type="text" name="description" class="edit-description-input" 
                                                   value="<?php echo htmlspecialchars($ip['description']); ?>" required>
                                            <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                                <button type="submit" class="btn btn-success btn-small">Save</button>
                                                <button type="button" class="btn btn-secondary btn-small" 
                                                        onclick="cancelEdit(<?php echo $ip['id']; ?>)">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($ip['enabled']): ?>
                                        <span class="badge badge-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($ip['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-primary btn-small" 
                                                onclick="editDescription(<?php echo $ip['id']; ?>)">Edit</button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_ip">
                                            <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-small">
                                                <?php echo $ip['enabled'] ? 'Disable' : 'Enable'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this IP from allowlist?');">
                                            <input type="hidden" name="action" value="delete_ip">
                                            <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function editDescription(ipId) {
            document.getElementById('desc-view-' + ipId).style.display = 'none';
            document.getElementById('desc-edit-' + ipId).classList.add('active');
        }
        
        function cancelEdit(ipId) {
            document.getElementById('desc-view-' + ipId).style.display = 'block';
            document.getElementById('desc-edit-' + ipId).classList.remove('active');
        }
    </script>
</body>
</html>
