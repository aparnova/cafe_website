<?php
session_start();
require 'db.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle order status updates and assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $response = ['success' => false, 'message' => ''];
        
        if ($_POST['action'] === 'update_status') {
            $order_id = intval($_POST['order_id']);
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $order_id);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Order status updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update order status.'];
            }
        }
        
        if ($_POST['action'] === 'assign_delivery') {
            $order_id = intval($_POST['order_id']);
            $delivery_boy_id = intval($_POST['delivery_boy_id']);
            
            // Check if order is cancelled before assigning
            $check_stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
            $check_stmt->bind_param("i", $order_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $order_data = $check_result->fetch_assoc();
                if ($order_data['status'] === 'Cancelled') {
                    $response = ['success' => false, 'message' => 'Cannot assign cancelled order.'];
                } else {
                    $stmt = $conn->prepare("UPDATE orders SET assigned_to = ?, status = 'Processing' WHERE id = ?");
                    $stmt->bind_param("ii", $delivery_boy_id, $order_id);
                    
                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Order assigned successfully!'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to assign order.'];
                    }
                }
            } else {
                $response = ['success' => false, 'message' => 'Order not found.'];
            }
        }

        // Order details handler
        if ($_POST['action'] === 'get_order_details') {
            $order_id = intval($_POST['order_id']);

            // Fetch order details with customer & delivery boy info
            $stmt = $conn->prepare("
                SELECT o.*, u.fullname AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                       db.name AS delivery_boy_name
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN delivery_boys db ON o.assigned_to = db.id
                WHERE o.id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order_result = $stmt->get_result();

            if ($order_result->num_rows > 0) {
                $order = $order_result->fetch_assoc();

                // Fetch order items
                $items_stmt = $conn->prepare("
                    SELECT m.name, oi.quantity, oi.price
                    FROM order_items oi
                    JOIN menu_items m ON oi.menu_id = m.id
                    WHERE oi.order_id = ?
                ");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();

                $items = [];
                while ($row = $items_result->fetch_assoc()) {
                    $items[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'order' => $order,
                    'items' => $items
                ]);
                exit();
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
                exit();
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// Fetch all orders with customer and delivery boy details
$orders_query = "
    SELECT o.*, 
           u.fullname as customer_name, 
           u.email as customer_email,
           u.phone as customer_phone,
           db.name as delivery_boy_name
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN delivery_boys db ON o.assigned_to = db.id 
    ORDER BY o.created_at DESC
";
$orders_result = $conn->query($orders_query);

// Fetch all delivery boys for assignment dropdown
$delivery_boys_query = "SELECT id, name, email FROM delivery_boys ORDER BY name";
$delivery_boys_result = $conn->query($delivery_boys_query);
$delivery_boys = [];
while ($row = $delivery_boys_result->fetch_assoc()) {
    $delivery_boys[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Westley's Resto Café</title>
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
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
        }

        .back-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }

        .filter-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
            font-size: 14px;
        }

        .filter-group select, .filter-group input {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .filter-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .filter-btn:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .orders-table th {
            background: var(--primary);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .orders-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .orders-table tr:hover {
            background: #f9fafb;
        }

        .order-id {
            font-weight: 600;
            color: var(--primary);
            font-size: 16px;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .customer-name {
            font-weight: 500;
            color: var(--primary);
        }

        .customer-phone {
            color: #6b7280;
            font-size: 12px;
        }

        .order-items {
            max-width: 280px;
        }

        .item {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .item-name {
            font-weight: 500;
            color: var(--primary);
        }

        .address {
            max-width: 220px;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }

        .order-total {
            font-weight: 600;
            color: var(--primary);
            font-size: 16px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #bfdbfe;
            color: #1e3a8a;
        }

        .status-out-for-delivery {
            background: #fef08a;
            color: #854d0e;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .delivery-info {
            font-size: 12px;
            color: #6b7280;
        }

        .delivery-assigned {
            color: var(--success);
            font-weight: 500;
        }

        .delivery-unassigned {
            color: var(--warning);
            font-style: italic;
        }

        .order-date {
            font-size: 12px;
            color: #6b7280;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-primary {
            background: var(--info);
            color: white;
        }

        .btn-success {
            background: var(--success);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
            padding: 30px;
            width: 90%;
            max-width: 500px;
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
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .modal-actions .action-btn {
            padding: 10px 20px;
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
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #d1d5db;
        }

        .view-details-btn {
            background: none;
            border: 1px solid var(--info);
            color: var(--info);
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background: var(--info);
            color: white;
        }

        .items-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
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

        @media (max-width: 1200px) {
            .orders-table th,
            .orders-table td {
                padding: 10px 8px;
            }
            
            .orders-table {
                font-size: 13px;
            }
        }

        @media (max-width: 768px) {
            .main {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .filter-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .table-container {
                overflow-x: auto;
            }

            .orders-table {
                min-width: 1000px;
            }

            .orders-table th,
            .orders-table td {
                padding: 8px 6px;
            }

            .modal {
                width: 95%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-receipt"></i> Order Management</h1>
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="filter-container">
        <div class="filter-row">
            <div class="filter-group">
                <label for="status-filter">Filter by Status</label>
                <select id="status-filter">
                    <option value="">All Orders</option>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="date-filter">Filter by Date</label>
                <input type="date" id="date-filter">
            </div>
            <div class="filter-group">
                <label for="search-input">Search Orders</label>
                <input type="text" id="search-input" placeholder="Search orders...">
            </div>
            <div class="filter-group">
                <button class="filter-btn" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>

    <div class="table-container">
        <?php if ($orders_result->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Address</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Delivery</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr data-status="<?php echo $order['status']; ?>" data-date="<?php echo date('Y-m-d', strtotime($order['created_at'])); ?>">
                            <td>
                                <div class="order-id">#<?php echo $order['id']; ?></div>
                            </td>
                            <td>
                                <div class="customer-info">
                                    <div class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="customer-phone">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="order-items">
                                <div class="items-preview">
                                    <?php
                                    $items_query = $conn->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON oi.menu_id = m.id WHERE oi.order_id = ?");
                                    $items_query->bind_param("i", $order['id']);
                                    $items_query->execute();
                                    $items_result = $items_query->get_result();
                                    
                                    $items_array = [];
                                    while ($item = $items_result->fetch_assoc()) {
                                        $items_array[] = $item;
                                    }
                                    
                                    $item_count = 0;
                                    foreach ($items_array as $item) {
                                        if ($item_count < 2) {
                                            echo '<div class="item"><span class="item-name">' . htmlspecialchars($item['name']) . '</span> x' . $item['quantity'] . '</div>';
                                            $item_count++;
                                        }
                                    }
                                    
                                    $total_items = count($items_array);
                                    if ($total_items > 2) {
                                        echo '<div class="item">+' . ($total_items - 2) . ' more items</div>';
                                    }
                                    
                                    if ($total_items == 0) {
                                        echo '<div class="item" style="color: #ef4444;">No items found</div>';
                                    }
                                    ?>
                                </div>
                                <button class="view-details-btn" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    View All
                                </button>
                            </td>
                            <td>
                                <div class="address"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
                            </td>
                            <td>
                                <div class="order-total">₹<?php echo number_format($order['total_price'], 2); ?></div>
                                <div style="font-size: 11px; color: #6b7280;">
                                    <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="delivery-info">
                                    <?php if ($order['delivery_boy_name']): ?>
                                        <div class="delivery-assigned">
                                            <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($order['delivery_boy_name']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="delivery-unassigned">
                                            <i class="fas fa-user-times"></i> Not Assigned
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                <div class="order-date"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="actions">
                                    <?php 
                                    // Only show assign/reassign buttons for orders that are not cancelled or delivered
                                    if ($order['status'] !== 'Cancelled' && $order['status'] !== 'Delivered'): ?>
                                        <?php if ($order['status'] === 'Pending' || !$order['delivery_boy_name']): ?>
                                            <button class="action-btn btn-primary" onclick="openAssignModal(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-user-plus"></i> 
                                                <?php echo $order['delivery_boy_name'] ? 'Reassign' : 'Assign'; ?>
                                            </button>
                                        <?php elseif ($order['delivery_boy_name'] && $order['status'] !== 'Delivered'): ?>
                                            <button class="action-btn btn-primary" onclick="openAssignModal(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-edit"></i> Reassign
                                            </button>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #6b7280;">
                                                <?php 
                                                switch($order['status']) {
                                                    case 'Processing':
                                                        echo 'Processing';
                                                        break;
                                                    case 'Out for Delivery':
                                                        echo 'Out for Delivery';
                                                        break;
                                                    default:
                                                        echo 'Ready to Assign';
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #6b7280;">
                                            <?php 
                                            switch($order['status']) {
                                                case 'Delivered':
                                                    echo '<i class="fas fa-check-circle" style="color: var(--success);"></i> Completed';
                                                    break;
                                                case 'Cancelled':
                                                    echo '<i class="fas fa-times-circle" style="color: var(--danger);"></i> Cancelled';
                                                    break;
                                                default:
                                                    echo 'No Action Available';
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Orders Found</h3>
                <p>There are currently no orders to display.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <h3><i class="fas fa-motorcycle"></i> Assign Order to Delivery Personnel</h3>
        <form id="assignForm">
            <div class="form-group">
                <label for="delivery-boy-select">Select Delivery Person</label>
                <select id="delivery-boy-select" name="delivery_boy_id" required>
                    <option value="">Choose a delivery person...</option>
                    <?php foreach ($delivery_boys as $boy): ?>
                        <option value="<?php echo $boy['id']; ?>">
                            <?php echo htmlspecialchars($boy['name']); ?> (<?php echo htmlspecialchars($boy['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="action-btn" onclick="closeAssignModal()">Cancel</button>
                <button type="submit" class="action-btn btn-primary loading-btn" id="assignBtn">
                    <i class="fas fa-user-plus"></i>
                    <span class="btn-text">Assign Order</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal-overlay" id="orderDetailsModal">
    <div class="modal">
        <h3><i class="fas fa-info-circle"></i> Order Details</h3>
        <div id="orderDetailsContent"></div>
        <div class="modal-actions">
            <button type="button" class="action-btn" onclick="closeOrderDetailsModal()">Close</button>
        </div>
    </div>
</div>

<!-- Notification -->
<div class="notification" id="notification"></div>

<script>
let currentOrderId = null;

function openAssignModal(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('assignModal');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        console.error('Assignment modal not found');
        showNotification('Modal not found. Please refresh the page.', 'error');
    }
}

function closeAssignModal() {
    const modal = document.getElementById('assignModal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    const form = document.getElementById('assignForm');
    if (form) {
        form.reset();
    }
    
    currentOrderId = null;
    
    // Reset button state
    const assignBtn = document.getElementById('assignBtn');
    if (assignBtn) {
        assignBtn.disabled = false;
        assignBtn.innerHTML = '<i class="fas fa-user-plus"></i><span class="btn-text">Assign Order</span>';
    }
}

function closeOrderDetailsModal() {
    const modal = document.getElementById('orderDetailsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function viewOrderDetails(orderId) {
    const modal = document.getElementById('orderDetailsModal');
    const content = document.getElementById('orderDetailsContent');
    
    if (!modal || !content) {
        console.error('Order details modal elements not found');
        showNotification('Modal elements not found. Please refresh the page.', 'error');
        return;
    }
    
    content.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div class="spinner" style="margin: 0 auto 15px; width: 24px; height: 24px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 10px;">Loading order details...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Fetch order details via AJAX
    const formData = new FormData();
    formData.append('action', 'get_order_details');
    formData.append('order_id', orderId);

    fetch('view_orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const order = data.order;
            const items = data.items;
            
            let itemsHtml = '';
            let totalItemsPrice = 0;
            
            if (items && items.length > 0) {
                items.forEach(item => {
                    const itemTotal = (parseFloat(item.price) || 0) * (parseInt(item.quantity) || 0);
                    totalItemsPrice += itemTotal;
                    itemsHtml += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
                            <div>
                                <div style="font-weight: 500; color: var(--primary);">${item.name || 'Unknown Item'}</div>
                                <div style="font-size: 12px; color: #6b7280;">₹${parseFloat(item.price || 0).toFixed(2)} each</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 500;">Qty: ${item.quantity || 0}</div>
                                <div style="color: var(--accent); font-weight: 600;">₹${itemTotal.toFixed(2)}</div>
                            </div>
                        </div>
                    `;
                });
            } else {
                itemsHtml = '<div style="text-align: center; padding: 20px; color: #6b7280;">No items found for this order</div>';
            }
            
            content.innerHTML = `
                <div style="color: var(--primary);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h4 style="margin-bottom: 10px; color: var(--accent);">
                                <i class="fas fa-user"></i> Customer Information
                            </h4>
                            <p><strong>Name:</strong> ${order.customer_name || 'N/A'}</p>
                            <p><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
                            <p><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</p>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 10px; color: var(--accent);">
                                <i class="fas fa-info-circle"></i> Order Information
                            </h4>
                            <p><strong>Order ID:</strong> #${order.id}</p>
                            <p><strong>Status:</strong> <span class="status-badge status-${(order.status || '').toLowerCase().replace(/ /g, '-')}">${order.status || 'Unknown'}</span></p>
                            <p><strong>Payment:</strong> ${(order.payment_method || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</p>
                            <p><strong>Date:</strong> ${order.created_at ? new Date(order.created_at).toLocaleString() : 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin-bottom: 10px; color: var(--accent);">
                            <i class="fas fa-map-marker-alt"></i> Delivery Address
                        </h4>
                        <p style="background: #f9fafb; padding: 15px; border-radius: 8px; border-left: 4px solid var(--accent);">
                            ${order.delivery_address || 'No address provided'}
                        </p>
                    </div>
                    
                    ${order.delivery_boy_name ? `
                        <div style="margin-bottom: 20px;">
                            <h4 style="margin-bottom: 10px; color: var(--accent);">
                                <i class="fas fa-motorcycle"></i> Assigned Delivery Personnel
                            </h4>
                            <p style="background: #d1fae5; padding: 15px; border-radius: 8px; color: #065f46; border-left: 4px solid var(--success);">
                                <i class="fas fa-user-check"></i> <strong>${order.delivery_boy_name}</strong>
                            </p>
                        </div>
                    ` : ''}
                    
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--accent);">
                            <i class="fas fa-utensils"></i> Order Items
                        </h4>
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; background: #f9fafb;">
                            ${itemsHtml}
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; margin-top: 15px; border-top: 2px solid var(--accent);">
                                <div style="font-size: 18px; font-weight: 600;">Total Amount:</div>
                                <div style="font-size: 20px; font-weight: 700; color: var(--accent);">₹${parseFloat(order.total_price || 0).toFixed(2)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div style="text-align: center; padding: 20px; color: var(--danger);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h3>Error Loading Order Details</h3>
                    <p>${data.message || 'Unknown error occurred'}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div style="text-align: center; padding: 20px; color: var(--danger);">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h3>Network Error</h3>
                <p>Failed to load order details. Please try again.</p>
                <button onclick="viewOrderDetails(${orderId})" style="margin-top: 15px; padding: 10px 20px; background: var(--accent); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-redo"></i> Retry
                </button>
            </div>
        `;
    });
}

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

function updateStatus(orderId, status) {
    if (!confirm(`Are you sure you want to mark this order as ${status}?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', status);

    fetch('view_orders.php', {
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

function applyFilters() {
    const statusFilter = document.getElementById('status-filter');
    const dateFilter = document.getElementById('date-filter');
    const searchInput = document.getElementById('search-input');
    const orderRows = document.querySelectorAll('.orders-table tbody tr');

    if (!statusFilter || !dateFilter || !searchInput) {
        console.error('Filter elements not found');
        return;
    }

    const statusValue = statusFilter.value;
    const dateValue = dateFilter.value;
    const searchValue = searchInput.value.toLowerCase();

    let visibleCount = 0;
    orderRows.forEach(row => {
        let showRow = true;

        if (statusValue && row.dataset.status !== statusValue) {
            showRow = false;
        }

        if (dateValue && row.dataset.date !== dateValue) {
            showRow = false;
        }

        if (searchValue && !row.textContent.toLowerCase().includes(searchValue)) {
            showRow = false;
        }

        row.style.display = showRow ? 'table-row' : 'none';
        if (showRow) visibleCount++;
    });
    
    showNotification(`Filtered results: ${visibleCount} orders found`, 'success');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Setup assign form handler
    const assignForm = document.getElementById('assignForm');
    if (assignForm) {
        assignForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const assignBtn = document.getElementById('assignBtn');
            const deliveryBoySelect = document.getElementById('delivery-boy-select');
            
            if (!deliveryBoySelect || !deliveryBoySelect.value) {
                showNotification('Please select a delivery person', 'error');
                return;
            }
            
            if (!assignBtn) {
                console.error('Assign button not found');
                return;
            }
            
            // Show loading state
            assignBtn.disabled = true;
            assignBtn.innerHTML = '<div class="spinner" style="width: 14px; height: 14px; border: 2px solid transparent; border-top: 2px solid currentColor; border-radius: 50%; animation: spin 1s linear infinite;"></div> <span class="btn-text">Assigning...</span>';
            
            const formData = new FormData();
            formData.append('action', 'assign_delivery');
            formData.append('order_id', currentOrderId);
            formData.append('delivery_boy_id', deliveryBoySelect.value);

            fetch('view_orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeAssignModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                    assignBtn.disabled = false;
                    assignBtn.innerHTML = '<i class="fas fa-user-plus"></i><span class="btn-text">Assign Order</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
                assignBtn.disabled = false;
                assignBtn.innerHTML = '<i class="fas fa-user-plus"></i><span class="btn-text">Assign Order</span>';
            });
        });
    }

    // Setup modal click handlers
    const assignModal = document.getElementById('assignModal');
    if (assignModal) {
        assignModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAssignModal();
            }
        });
    }

    const orderDetailsModal = document.getElementById('orderDetailsModal');
    if (orderDetailsModal) {
        orderDetailsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderDetailsModal();
            }
        });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAssignModal();
            closeOrderDetailsModal();
        }
    });

    // Live search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const orderRows = document.querySelectorAll('.orders-table tbody tr');
            
            orderRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? 'table-row' : 'none';
            });
        });
    }
});
</script>

</body>
</html>