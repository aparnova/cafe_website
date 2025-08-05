<?php
session_start();
require_once 'db.php';

// --- Basic note: protect this page with authentication in production ---
// For example, check if $_SESSION['is_admin'] is set. Here we assume admin access.

//
// CSRF token for status update
//
if (empty($_SESSION['admin_token'])) {
    $_SESSION['admin_token'] = bin2hex(random_bytes(24));
}
$admin_token = $_SESSION['admin_token'];

// Handle POST: update status (non-AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Simple CSRF check
    $token = $_POST['admin_token'] ?? '';
    if (!hash_equals($_SESSION['admin_token'], $token)) {
        $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Invalid request token.'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $booking_id = trim($_POST['booking_id'] ?? '');
    $new_status = trim($_POST['status'] ?? '');

    $allowed = ['pending', 'confirmed', 'cancelled'];
    if (empty($booking_id) || !in_array($new_status, $allowed, true)) {
        $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Invalid input.'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Update using prepared statement
    $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE booking_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $new_status, $booking_id);
        if ($stmt->execute()) {
            $_SESSION['admin_notice'] = ['type' => 'success', 'text' => "Status updated for $booking_id."];
        } else {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Database error: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Prepare statement failed: ' . $conn->error];
    }

    // PRG: redirect back to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch reservations (latest first)
$limit = 100; // tune as needed, or add pagination
$stmt = $conn->prepare("SELECT id, name, email, phone, date, time, people, message, booking_id, status, created_at FROM reservations ORDER BY created_at DESC LIMIT ?");
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();
$reservations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// mapping status -> display message
function status_text($status) {
    if ($status === 'confirmed') return 'Your booking is accepted';
    if ($status === 'cancelled') return 'Sorry, your booking was not accepted';
    return 'Pending admin approval';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Admin Dashboard — Reservations</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    body { font-family: Arial, Helvetica, sans-serif; background:#f4f4f6; color:#111; padding:20px; }
    .wrap { max-width:1200px; margin:0 auto; }
    h1 { margin-bottom:10px; }
    .notice { padding:10px 14px; border-radius:6px; margin-bottom:12px; }
    .success { background:#e6ffef; border:1px solid #8fe7b6; color:#0a6b36; }
    .error { background:#ffecec; border:1px solid #f1a3a3; color:#7a1b1b; }
    table { width:100%; border-collapse:collapse; background:white; border-radius:6px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.06); }
    th, td { padding:10px 12px; border-bottom:1px solid #eee; text-align:left; font-size:14px; vertical-align:middle; }
    th { background:#fafafa; font-weight:600; }
    tr:last-child td { border-bottom:none; }
    .small { font-size:12px; color:#666; }
    .controls { display:flex; gap:8px; align-items:center; }
    .btn { padding:8px 12px; border-radius:6px; border:1px solid #bbb; background:#fff; cursor:pointer; font-size:13px; }
    .btn-primary { background:#2e8b57; color:white; border-color:#2e8b57; }
    .btn-danger { background:#d9534f; color:white; border-color:#d9534f; }
    select { padding:6px 8px; border-radius:6px; }
    .auto-refresh { margin-left:8px; display:inline-flex; align-items:center; gap:6px; }
    .meta { margin-bottom:10px; color:#444; }
    .booking-id { font-family:monospace; background:#f4f4f6; padding:3px 6px; border-radius:4px; font-size:13px; }
    .status-badge { font-size:13px; padding:6px 8px; border-radius:6px; display:inline-block; color:#fff; }
    .s-pending { background:#f0ad4e; }
    .s-confirmed { background:#28a745; }
    .s-cancelled { background:#d9534f; }
    .message { white-space:pre-wrap; max-width:320px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Reservations — Admin</h1>

    <div class="meta">
      Showing latest <?php echo htmlspecialchars($limit); ?> reservations.
      <label class="auto-refresh">
        <input type="checkbox" id="autoRefresh" checked /> Auto refresh (every 8s)
      </label>
      <button class="btn" onclick="location.reload()">Refresh now</button>
    </div>

    <?php if (!empty($_SESSION['admin_notice'])): 
        $n = $_SESSION['admin_notice']; 
        $cls = ($n['type'] === 'success') ? 'success' : 'error';
    ?>
      <div class="notice <?php echo $cls; ?>"><?php echo htmlspecialchars($n['text']); ?></div>
    <?php unset($_SESSION['admin_notice']); endif; ?>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Booking ID</th>
          <th>Guest</th>
          <th>Contact</th>
          <th>Date & Time</th>
          <th>Guests</th>
          <th>Message</th>
          <th>Status</th>
          <th>Admin Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reservations)): ?>
          <tr><td colspan="9" class="small">No reservations yet.</td></tr>
        <?php else: ?>
          <?php foreach ($reservations as $i => $r): ?>
            <?php
              $num = $i + 1;
              $displayDate = date("F j, Y", strtotime($r['date']));
              $displayTime = date("g:i A", strtotime($r['time']));
              $status = $r['status'];
              $statusClass = $status === 'confirmed' ? 's-confirmed' : ($status === 'cancelled' ? 's-cancelled' : 's-pending');
            ?>
            <tr>
              <td><?php echo $num; ?></td>
              <td><span class="booking-id"><?php echo htmlspecialchars($r['booking_id']); ?></span>
                  <div class="small">Created: <?php echo htmlspecialchars($r['created_at']); ?></div>
              </td>
              <td>
                <?php echo htmlspecialchars($r['name']); ?><br>
                <div class="small"><?php echo htmlspecialchars($r['email']); ?></div>
              </td>
              <td><div class="small"><?php echo htmlspecialchars($r['phone']); ?></div></td>
              <td>
                <?php echo $displayDate; ?><br>
                <div class="small"><?php echo $displayTime; ?></div>
              </td>
              <td><?php echo (int)$r['people']; ?></td>
              <td class="message"><?php echo htmlspecialchars($r['message']); ?></td>
              <td>
                <div class="status-badge <?php echo $statusClass; ?>">
                  <?php echo ucfirst($status); ?>
                </div>
                <div class="small"><?php echo htmlspecialchars(status_text($status)); ?></div>
              </td>
              <td>
                <!-- Status update form (non-AJAX) -->
                <form method="post" style="display:flex; gap:6px; align-items:center;">
                  <input type="hidden" name="action" value="update_status" />
                  <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($r['booking_id']); ?>" />
                  <input type="hidden" name="admin_token" value="<?php echo $admin_token; ?>" />
                  <select name="status" aria-label="Change status">
                    <option value="pending" <?php if ($status === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="confirmed" <?php if ($status === 'confirmed') echo 'selected'; ?>>Confirm</option>
                    <option value="cancelled" <?php if ($status === 'cancelled') echo 'selected'; ?>>Cancel</option>
                  </select>
                  <button type="submit" class="btn btn-primary">Update</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <p class="small" style="margin-top:12px;">
      Note: For production, protect this page with authentication and use HTTPS. Auto-refresh reloads the page every 8 seconds — it is a full-page refresh (no AJAX).
    </p>
  </div>

<script>
  // Auto refresh logic (non-AJAX). Reloads page every 8 seconds when enabled.
  (function(){
    var auto = document.getElementById('autoRefresh');
    var interval = null;
    function start() {
      if (interval) clearInterval(interval);
      interval = setInterval(function() {
        // only refresh when page is visible (to avoid background reloads)
        if (document.visibilityState === 'visible') {
          location.reload();
        }
      }, 8000);
    }
    function stop() {
      if (interval) clearInterval(interval);
      interval = null;
    }
    auto.addEventListener('change', function() {
      if (auto.checked) start(); else stop();
    });
    // start by default if checked
    if (auto.checked) start();
    // stop refresh if admin focuses any input/select to avoid disruptions
    document.addEventListener('focusin', function(e){
      // if focus is on a select or input inside the table, pause autoreload
      if (e.target.tagName === 'SELECT' || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        stop();
      }
    });
    // resume when focus out and checkbox is still checked
    document.addEventListener('focusout', function(){
      if (auto.checked) start();
    });
  })();
</script>
</body>
</html>
