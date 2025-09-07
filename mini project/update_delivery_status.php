<?php
session_start();
require 'db.php';

// Only delivery personnel can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: homepage.php");
    exit();
}

$delivery_boy_id = $_SESSION['user_id'];

// Handle status update
if (isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // Ensure the order is assigned to this delivery boy
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("sii", $status, $order_id, $delivery_boy_id);
    $stmt->execute();

    $_SESSION['msg'] = "Order #$order_id status updated to $status.";
    header("Location: update_delivery_status.php");
    exit();
}

// Fetch orders assigned to this delivery boy
$stmt = $conn->prepare("
    SELECT o.id, u.fullname AS customer, o.total_price, o.delivery_address, o.payment_method, o.status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.assigned_to = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $delivery_boy_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Delivery Status</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
    h1 { color: #111827; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
    th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
    th { background: #111827; color: #fff; }
    select, button { padding: 5px 10px; margin-top: 5px; }
    button { background: #f59e0b; color: #fff; border: none; cursor: pointer; border-radius: 5px; }
    button:hover { background: #d97706; }
    .status { padding: 5px 10px; border-radius: 5px; font-weight: bold; }
    .Pending { background: #fef3c7; color: #92400e; }
    .Assigned { background: #bfdbfe; color: #1e3a8a; }
    .Processing { background: #fef08a; color: #854d0e; }
    .Out_for_Delivery { background: #d1fae5; color: #065f46; }
    .Delivered { background: #d1fae5; color: #065f46; }
    .Cancelled { background: #fee2e2; color: #991b1b; }
    .msg { margin: 10px 0; padding: 10px; background: #d1fae5; color: #065f46; border-radius: 5px; }
</style>
</head>
<body>

<h1>Update Delivery Status</h1>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="msg"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<?php if ($result->num_rows === 0): ?>
    <p>No orders assigned to you yet.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Address</th>
            <th>Payment</th>
            <th>Current Status</th>
            <th>Update Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= $row['customer']; ?></td>
            <td>₹<?= $row['total_price']; ?></td>
            <td><?= htmlspecialchars($row['delivery_address']); ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></td>
            <td class="status <?= str_replace(' ', '_', $row['status']); ?>"><?= $row['status']; ?></td>
            <td>
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="order_id" value="<?= $row['id']; ?>">
                    <select name="status" required>
                        <option value="">Select Status</option>
                        <option value="Processing">Processing</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                    </select>
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

</body>
</html>
