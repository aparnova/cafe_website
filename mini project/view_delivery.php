<?php
session_start();
require 'db.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_delivery_boy':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $password = trim($_POST['password']);
                $phone = trim($_POST['phone']);
                
                // Validate inputs
                if (empty($name) || empty($email) || empty($password) || empty($phone)) {
                    $response = ['success' => false, 'message' => 'All fields are required.'];
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response = ['success' => false, 'message' => 'Invalid email format.'];
                    break;
                }
                
                if (strlen($password) < 6) {
                    $response = ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
                    break;
                }
                
                // Check if email already exists
                $check_stmt = $conn->prepare("SELECT id FROM delivery_boys WHERE email = ?");
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $response = ['success' => false, 'message' => 'Email already exists.'];
                    break;
                }
                
                // Hash password and insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO delivery_boys (name, email, password, phone) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Delivery personnel added successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to add delivery personnel.'];
                }
                break;
                
            case 'update_delivery_boy':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $password = trim($_POST['password']);
                
                // Validate inputs
                if (empty($name) || empty($email) || empty($phone)) {
                    $response = ['success' => false, 'message' => 'Name, email, and phone are required.'];
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response = ['success' => false, 'message' => 'Invalid email format.'];
                    break;
                }
                
                // Check if email already exists for other users
                $check_stmt = $conn->prepare("SELECT id FROM delivery_boys WHERE email = ? AND id != ?");
                $check_stmt->bind_param("si", $email, $id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $response = ['success' => false, 'message' => 'Email already exists for another delivery personnel.'];
                    break;
                }
                
                // Update with or without password
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $response = ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
                        break;
                    }
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE delivery_boys SET name = ?, email = ?, password = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $email, $hashed_password, $phone, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE delivery_boys SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $phone, $id);
                }
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Delivery personnel updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update delivery personnel.'];
                }
                break;
                
            case 'delete_delivery_boy':
                $id = intval($_POST['id']);
                
                // Check if delivery boy has any active orders
                $check_orders = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE assigned_to = ? AND status NOT IN ('Delivered', 'Cancelled')");
                $check_orders->bind_param("i", $id);
                $check_orders->execute();
                $order_count = $check_orders->get_result()->fetch_assoc()['count'];
                
                if ($order_count > 0) {
                    $response = ['success' => false, 'message' => 'Cannot delete delivery personnel with active orders.'];
                    break;
                }
                
                $stmt = $conn->prepare("DELETE FROM delivery_boys WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Delivery personnel deleted successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to delete delivery personnel.'];
                }
                break;
                
            case 'get_delivery_boy':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("SELECT * FROM delivery_boys WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $delivery_boy = $result->fetch_assoc();
                    echo json_encode(['success' => true, 'data' => $delivery_boy]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Delivery personnel not found.']);
                }
                exit();
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// Fetch all delivery boys with their statistics
$delivery_boys_query = "
    SELECT db.*, 
           COUNT(CASE WHEN o.status IN ('Processing', 'Out for Delivery') THEN 1 END) as active_orders,
           COUNT(CASE WHEN o.status = 'Delivered' AND DATE(o.created_at) = CURDATE() THEN 1 END) as today_delivered,
           COUNT(o.id) as total_orders
    FROM delivery_boys db 
    LEFT JOIN orders o ON db.id = o.assigned_to 
    GROUP BY db.id
    ORDER BY db.name ASC
";
$delivery_boys_result = $conn->query($delivery_boys_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Management - Westley's Resto Café</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #111827;
            --secondary: #1f2937;
            --accent: #f59e0b;
            --light: #f3f4f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            color: #333;
            overflow-x: hidden;
        }

        .main {
            padding: 20px;
            width: 100%;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-bottom: 1px solid #e5e7eb;
        }

        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .back-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .back-btn:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }

        .add-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
        }

        .add-btn:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
            border-left: 4px solid var(--accent);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 48px;
            color: var(--accent);
            margin-bottom: 20px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: #6b7280;
            font-weight: 500;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .delivery-table {
            width: 100%;
            border-collapse: collapse;
        }

        .delivery-table th {
            background: var(--primary);
            color: white;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .delivery-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .delivery-table tr:hover {
            background: #f9fafb;
        }

        .delivery-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .delivery-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }

        .delivery-details h4 {
            margin: 0 0 5px 0;
            color: var(--primary);
            font-weight: 600;
            font-size: 16px;
        }

        .delivery-details p {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .contact-info p {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .contact-info i {
            width: 16px;
            color: var(--accent);
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .stats-mini {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 13px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
        }

        .stat-item i {
            width: 16px;
        }

        .stat-item.active {
            color: var(--warning);
        }

        .stat-item.delivered {
            color: var(--success);
        }

        .action-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 3px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--info);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(2px);
        }

        .modal {
            background: white;
            border-radius: 12px;
            padding: 35px;
            width: 90%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px rgba(0,0,0,0.1);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal h3 {
            margin-bottom: 25px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .modal-actions .action-btn {
            padding: 12px 24px;
            font-size: 14px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 3000;
            display: none;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 400px;
        }

        .notification.success {
            background: var(--success);
        }

        .notification.error {
            background: var(--danger);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 80px;
            margin-bottom: 25px;
            color: #d1d5db;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .loading-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .loading-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .spinner {
            width: 12px;
            height: 12px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .password-note {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .main {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .stats-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .table-container {
                overflow-x: auto;
            }

            .delivery-table {
                min-width: 800px;
            }

            .modal {
                width: 95%;
                padding: 25px;
            }

            .delivery-info {
                gap: 15px;
            }

            .delivery-avatar {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-truck"></i> Delivery Management</h1>
        <div class="header-actions">
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button class="add-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i>
                Add Delivery Personnel
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $delivery_boys_result->num_rows; ?></div>
            <div class="stat-label">Total Delivery Personnel</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-value">
                <?php 
                $active_query = $conn->query("SELECT COUNT(DISTINCT assigned_to) as count FROM orders WHERE status IN ('Processing', 'Out for Delivery') AND assigned_to IS NOT NULL");
                echo $active_query->fetch_assoc()['count'];
                ?>
            </div>
            <div class="stat-label">Currently Active</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value">
                <?php 
                $delivered_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Delivered' AND DATE(created_at) = CURDATE()");
                echo $delivered_query->fetch_assoc()['count'];
                ?>
            </div>
            <div class="stat-label">Orders Delivered Today</div>
        </div>
    </div>

    <!-- Delivery Personnel Table -->
    <div class="table-container">
        <?php if ($delivery_boys_result->num_rows > 0): ?>
            <table class="delivery-table">
                <thead>
                    <tr>
                        <th>Delivery Personnel</th>
                        <th>Contact Information</th>
                        <th>Current Status</th>
                        <th>Performance Statistics</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($delivery_boy = $delivery_boys_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="delivery-info">
                                    <div class="delivery-avatar">
                                        <?php echo strtoupper(substr($delivery_boy['name'], 0, 1)); ?>
                                    </div>
                                    <div class="delivery-details">
                                        <h4><?php echo htmlspecialchars($delivery_boy['name']); ?></h4>
                                        <p>Personnel ID: #<?php echo $delivery_boy['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($delivery_boy['email']); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($delivery_boy['phone']); ?></p>
                                </div>
                            </td>
                            <td>
                                <?php if ($delivery_boy['active_orders'] > 0): ?>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-truck"></i> On Delivery
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-check-circle"></i> Available
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="stats-mini">
                                    <div class="stat-item active">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo $delivery_boy['active_orders']; ?> Active Orders</span>
                                    </div>
                                    <div class="stat-item delivered">
                                        <i class="fas fa-check"></i>
                                        <span><?php echo $delivery_boy['today_delivered']; ?> Delivered Today</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-list"></i>
                                        <span><?php echo $delivery_boy['total_orders']; ?> Total Orders</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button class="action-btn btn-primary" onclick="openEditModal(<?php echo $delivery_boy['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn btn-danger" onclick="deleteDeliveryBoy(<?php echo $delivery_boy['id']; ?>, '<?php echo htmlspecialchars($delivery_boy['name']); ?>', <?php echo $delivery_boy['active_orders']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-truck"></i>
                <h3>No Delivery Personnel</h3>
                <p>Start by adding your first delivery person to manage orders efficiently.</p>
                <button class="add-btn" onclick="openAddModal()" style="margin-top: 25px;">
                    <i class="fas fa-plus"></i>
                    Add Delivery Personnel
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h3><i class="fas fa-plus"></i> Add Delivery Personnel</h3>
        <form id="addForm">
            <div class="form-group">
                <label for="add-name">Full Name</label>
                <input type="text" id="add-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="add-email">Email Address</label>
                <input type="email" id="add-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="add-phone">Phone Number</label>
                <input type="tel" id="add-phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="add-password">Password</label>
                <input type="password" id="add-password" name="password" required>
                <div class="password-note">Password must be at least 6 characters long</div>
            </div>
            <div class="modal-actions">
                <button type="button" class="action-btn" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="action-btn btn-primary loading-btn" id="addBtn">
                    <i class="fas fa-plus"></i>
                    <span class="btn-text">Add Personnel</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3><i class="fas fa-edit"></i> Edit Delivery Personnel</h3>
        <form id="editForm">
            <input type="hidden" id="edit-id" name="id">
            <div class="form-group">
                <label for="edit-name">Full Name</label>
                <input type="text" id="edit-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit-email">Email Address</label>
                <input type="email" id="edit-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="edit-phone">Phone Number</label>
                <input type="tel" id="edit-phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="edit-password">New Password (Optional)</label>
                <input type="password" id="edit-password" name="password">
                <div class="password-note">Leave blank to keep current password</div>
            </div>
            <div class="modal-actions">
                <button type="button" class="action-btn" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="action-btn btn-primary loading-btn" id="editBtn">
                    <i class="fas fa-save"></i>
                    <span class="btn-text">Update Personnel</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Notification -->
<div class="notification" id="notification"></div>

<script>
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    if (!notification) {
        console.error('Notification element not found');
        alert(message);
        return;
    }
    
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 4000);
}

function openAddModal() {
    const modal = document.getElementById('addModal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('add-name').focus();
    }
}

function closeAddModal() {
    const modal = document.getElementById('addModal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    const form = document.getElementById('addForm');
    if (form) {
        form.reset();
    }
    
    // Reset button state
    const addBtn = document.getElementById('addBtn');
    if (addBtn) {
        addBtn.disabled = false;
        addBtn.innerHTML = '<i class="fas fa-plus"></i><span class="btn-text">Add Personnel</span>';
    }
}

function openEditModal(id) {
    const modal = document.getElementById('editModal');
    if (!modal) {
        console.error('Edit modal not found');
        return;
    }

    // Show loading state
    const editContent = modal.querySelector('.modal');
    editContent.style.opacity = '0.7';
    
    // Fetch delivery boy data
    const formData = new FormData();
    formData.append('action', 'get_delivery_boy');
    formData.append('id', id);

    fetch('view_delivery.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        editContent.style.opacity = '1';
        
        if (data.success) {
            document.getElementById('edit-id').value = data.data.id;
            document.getElementById('edit-name').value = data.data.name;
            document.getElementById('edit-email').value = data.data.email;
            document.getElementById('edit-phone').value = data.data.phone;
            document.getElementById('edit-password').value = '';
            
            modal.style.display = 'flex';
            document.getElementById('edit-name').focus();
        } else {
            showNotification(data.message || 'Failed to load delivery personnel data', 'error');
        }
    })
    .catch(error => {
        editContent.style.opacity = '1';
        console.error('Error:', error);
        showNotification('An error occurred while loading data', 'error');
    });
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    const form = document.getElementById('editForm');
    if (form) {
        form.reset();
    }
    
    // Reset button state
    const editBtn = document.getElementById('editBtn');
    if (editBtn) {
        editBtn.disabled = false;
        editBtn.innerHTML = '<i class="fas fa-save"></i><span class="btn-text">Update Personnel</span>';
    }
}

function deleteDeliveryBoy(id, name, activeOrders) {
    if (activeOrders > 0) {
        showNotification(`Cannot delete ${name}. They have ${activeOrders} active order(s).`, 'error');
        return;
    }

    if (!confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_delivery_boy');
    formData.append('id', id);

    fetch('view_delivery.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Setup add form handler
    const addForm = document.getElementById('addForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const addBtn = document.getElementById('addBtn');
            if (!addBtn) return;
            
            // Show loading state
            addBtn.disabled = true;
            addBtn.innerHTML = '<div class="spinner"></div> <span class="btn-text">Adding...</span>';
            
            const formData = new FormData(this);
            formData.append('action', 'add_delivery_boy');

            fetch('view_delivery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeAddModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                    // Reset button state on error
                    addBtn.disabled = false;
                    addBtn.innerHTML = '<i class="fas fa-plus"></i><span class="btn-text">Add Personnel</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
                // Reset button state on error
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="fas fa-plus"></i><span class="btn-text">Add Personnel</span>';
            });
        });
    }

    // Setup edit form handler
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const editBtn = document.getElementById('editBtn');
            if (!editBtn) return;
            
            // Show loading state
            editBtn.disabled = true;
            editBtn.innerHTML = '<div class="spinner"></div> <span class="btn-text">Updating...</span>';
            
            const formData = new FormData(this);
            formData.append('action', 'update_delivery_boy');

            fetch('view_delivery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeEditModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                    // Reset button state on error
                    editBtn.disabled = false;
                    editBtn.innerHTML = '<i class="fas fa-save"></i><span class="btn-text">Update Personnel</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
                // Reset button state on error
                editBtn.disabled = false;
                editBtn.innerHTML = '<i class="fas fa-save"></i><span class="btn-text">Update Personnel</span>';
            });
        });
    }

    // Setup modal click handlers
    const addModal = document.getElementById('addModal');
    if (addModal) {
        addModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    }

    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAddModal();
            closeEditModal();
        }
    });

    // Auto-refresh every 5 minutes to get updated statistics
    setInterval(() => {
        location.reload();
    }, 300000);
});
</script>

</body>
</html>