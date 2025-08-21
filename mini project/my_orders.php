<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE fullname = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$user_id = $userData['id'];

// Fetch user orders with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM orders 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders - Westley's Resto Cafe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Font & Color Variables */
    :root {
      --default-font: "Roboto", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      --heading-font: "Playfair Display", sans-serif;
      --nav-font: "Poppins", sans-serif;
      --background-color: #0c0b09;
      --default-color: rgba(255, 255, 255, 0.7);
      --heading-color: #ffffff;
      --accent-color: #cda45e;
      --surface-color: #29261f;
      --contrast-color: #0c0b09;
    }

    /* General Styles */
    body {
      color: var(--default-color);
      background-color: var(--background-color);
      font-family: var(--default-font);
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }

    .container {
      width: 100%;
      max-width: 1140px;
      margin: 0 auto;
      padding: 0 15px;
    }

    /* Header Styles */
    .header {
      background-color: var(--background-color);
      color: var(--default-color);
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 997;
      border-bottom: 1px solid var(--surface-color);
    }

    .header .branding {
      min-height: 60px;
      padding: 10px 0;
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header .logo {
      display: flex;
      align-items: center;
    }

    .header .logo img {
      height: 50px;
      margin-right: 15px;
    }

    .header .logo h1 {
      font-size: 24px;
      margin: 0;
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    .header-nav {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .nav-link {
      color: var(--default-color);
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 25px;
      transition: all 0.3s;
      font-family: var(--nav-font);
    }

    .nav-link:hover,
    .nav-link.active {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    /* Main Content */
    .main-content {
      padding-top: 100px;
      padding-bottom: 60px;
      min-height: 100vh;
    }

    .page-title {
      text-align: center;
      margin-bottom: 40px;
    }

    .page-title h1 {
      color: var(--accent-color);
      font-family: var(--heading-font);
      font-size: 36px;
      margin: 0 0 10px;
    }

    .page-title p {
      color: var(--default-color);
      margin: 0;
      font-size: 18px;
    }

    /* Orders List */
    .orders-container {
      display: grid;
      gap: 20px;
    }

    .order-card {
      background: var(--surface-color);
      border-radius: 15px;
      padding: 25px;
      border: 1px solid color-mix(in srgb, var(--accent-color), transparent 70%);
      transition: transform 0.3s, border-color 0.3s;
    }

    .order-card:hover {
      transform: translateY(-3px);
      border-color: var(--accent-color);
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .order-id {
      color: var(--accent-color);
      font-size: 20px;
      font-weight: bold;
    }

    .order-date {
      color: var(--default-color);
      font-size: 14px;
    }

    .order-status {
      padding: 6px 15px;
      border-radius: 25px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-pending {
      background: #ff9800;
      color: white;
    }

    .status-confirmed {
      background: #2196F3;
      color: white;
    }

    .status-preparing {
      background: #FF5722;
      color: white;
    }

    .status-ready {
      background: #4CAF50;
      color: white;
    }

    .status-delivered {
      background: #4CAF50;
      color: white;
    }

    .status-cancelled {
      background: #f44336;
      color: white;
    }

    .order-details {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
      align-items: center;
    }

    .order-info h4 {
      color: var(--heading-color);
      margin: 0 0 10px;
      font-size: 16px;
    }

    .order-info p {
      color: var(--default-color);
      margin: 5px 0;
      font-size: 14px;
    }

    .order-amount {
      text-align: right;
    }

    .amount {
      color: var(--accent-color);
      font-size: 24px;
      font-weight: bold;
    }

    .order-actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .btn {
      padding: 8px 20px;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s;
      font-family: var(--nav-font);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn-primary {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    .btn-primary:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    .btn-secondary {
      background: transparent;
      color: var(--accent-color);
      border: 1px solid var(--accent-color);
    }

    .btn-secondary:hover {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state i {
      font-size: 80px;
      color: color-mix(in srgb, var(--default-color), transparent 60%);
      margin-bottom: 20px;
    }

    .empty-state h3 {
      color: var(--heading-color);
      font-size: 24px;
      margin-bottom: 15px;
    }

    .empty-state p {
      color: var(--default-color);
      margin-bottom: 30px;
      font-size: 16px;
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 40px;
      gap: 10px;
    }

    .pagination a,
    .pagination span {
      padding: 10px 15px;
      border: 1px solid var(--accent-color);
      border-radius: 5px;
      text-decoration: none;
      color: var(--accent-color);
      transition: all 0.3s;
    }

    .pagination a:hover,
    .pagination .current {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    .pagination .disabled {
      opacity: 0.5;
      pointer-events: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .order-details {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .order-actions {
        justify-content: center;
      }

      .header-nav {
        gap: 10px;
      }

      .nav-link {
        padding: 8px 15px;
        font-size: 14px;
      }

      .page-title h1 {
        font-size: 28px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="branding">
      <div class="container">
        <div class="logo">
          <img src="img.png" alt="Westley's Resto Cafe">
          <h1>Westley's Resto Cafe</h1>
        </div>
        <nav class="header-nav">
          <a href="menu.php" class="nav-link">
            <i class="fas fa-utensils"></i> Menu
          </a>
          <a href="my_orders.php" class="nav-link active">
            <i class="fas fa-list"></i> My Orders
          </a>
          <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </nav>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container">
      <div class="page-title">
        <h1>My Orders</h1>
        <p>Track your order history and status</p>
      </div>

      <div class="orders-container">
        <?php if (empty($orders)): ?>
          <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <h3>No Orders Yet</h3>
            <p>You haven't placed any orders yet. Start by exploring our delicious menu!</p>
            <a href="menu.php" class="btn btn-primary">
              <i class="fas fa-utensils"></i>
              Browse Menu
            </a>
          </div>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <div class="order-card">
              <div class="order-header">
                <div>
                  <div class="order-id">Order #<?php echo $order['id']; ?></div>
                  <div class="order-date"><?php echo date('M d, Y at h:i A', strtotime($order['created_at'])); ?></div>
                </div>
                <span class="order-status status-<?php echo $order['status']; ?>">
                  <?php echo ucfirst($order['status']); ?>
                </span>
              </div>

              <div class="order-details">
                <div class="order-info">
                  <h4>Delivery Address</h4>
                  <p><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                  <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                  <?php if ($order['notes']): ?>
                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                  <?php endif; ?>
                </div>
                <div class="order-amount">
                  <div class="amount">₹<?php echo $order['total_amount']; ?></div>
                </div>
              </div>

              <div class="order-actions">
                <a href="order_confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                  <i class="fas fa-eye"></i>
                  View Details
                </a>
                <?php if ($order['status'] === 'pending' || $order['status'] === 'confirmed'): ?>
                  <button class="btn btn-secondary" onclick="showCancelDialog(<?php echo $order['id']; ?>)">
                    <i class="fas fa-times"></i>
                    Cancel Order
                  </button>
                <?php endif; ?>
                <?php if ($order['status'] === 'delivered'): ?>
                  <button class="btn btn-secondary" onclick="reorderItems(<?php echo $order['id']; ?>)">
                    <i class="fas fa-redo"></i>
                    Reorder
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="pagination">
              <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">
                  <i class="fas fa-chevron-left"></i> Previous
                </a>
              <?php else: ?>
                <span class="disabled">
                  <i class="fas fa-chevron-left"></i> Previous
                </span>
              <?php endif; ?>

              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                  <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                  <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">
                  Next <i class="fas fa-chevron-right"></i>
                </a>
              <?php else: ?>
                <span class="disabled">
                  Next <i class="fas fa-chevron-right"></i>
                </span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Cancel Order Modal -->
  <div id="cancel-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--surface-color); padding: 30px; border-radius: 15px; max-width: 400px; text-align: center; border: 2px solid var(--accent-color);">
      <h3 style="color: var(--accent-color); margin-bottom: 20px;">Cancel Order</h3>
      <p style="color: var(--default-color); margin-bottom: 25px;">Are you sure you want to cancel this order? This action cannot be undone.</p>
      <div style="display: flex; gap: 15px; justify-content: center;">
        <button onclick="cancelOrder()" class="btn btn-primary">Yes, Cancel Order</button>
        <button onclick="closeCancelDialog()" class="btn btn-secondary">No, Keep Order</button>
      </div>
    </div>
  </div>

  <!-- Notification -->
  <div id="notification" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--accent-color); color: var(--contrast-color); padding: 15px 25px; border-radius: 8px; display: none; z-index: 1001;">
    Notification message
  </div>

  <script>
    let orderToCancel = null;

    function showCancelDialog(orderId) {
      orderToCancel = orderId;
      document.getElementById('cancel-modal').style.display = 'flex';
    }

    function closeCancelDialog() {
      orderToCancel = null;
      document.getElementById('cancel-modal').style.display = 'none';
    }

    async function cancelOrder() {
      if (!orderToCancel) return;

      try {
        const response = await fetch('cancel_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ order_id: orderToCancel })
        });

        const result = await response.json();

        if (result.success) {
          showNotification('Order cancelled successfully!', 'success');
          setTimeout(() => location.reload(), 1500);
        } else {
          showNotification(result.message || 'Failed to cancel order', 'error');
        }
      } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
      }

      closeCancelDialog();
    }

    async function reorderItems(orderId) {
      try {
        const response = await fetch(`get_order_items.php?order_id=${orderId}`);
        const result = await response.json();

        if (result.success) {
          // Store items in localStorage for reorder
          const cartKey = `cart_<?php echo $_SESSION['user']; ?>`;
          localStorage.setItem(cartKey, JSON.stringify(result.items));
          
          showNotification('Items added to cart!', 'success');
          setTimeout(() => {
            window.location.href = 'menu.php';
          }, 1500);
        } else {
          showNotification('Failed to reorder items', 'error');
        }
      } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
      }
    }

    function showNotification(message, type = 'success') {
      const notification = document.getElementById('notification');
      notification.textContent = message;
      notification.style.background = type === 'error' ? '#ff6b6b' : 'var(--accent-color)';
      notification.style.display = 'block';

      setTimeout(() => {
        notification.style.display = 'none';
      }, 4000);
    }

    // Close modal when clicking outside
    document.getElementById('cancel-modal').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) {
        closeCancelDialog();
      }
    });
  </script>
</body>
</html>