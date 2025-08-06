<?php
session_start();
require_once 'db.php'; // make sure this sets up $conn (mysqli)

// CSRF token for status update
if (empty($_SESSION['admin_token'])) {
    $_SESSION['admin_token'] = bin2hex(random_bytes(24));
}
$admin_token = $_SESSION['admin_token'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        // CSRF check
        $token = $_POST['admin_token'] ?? '';
        if (!hash_equals($_SESSION['admin_token'], $token)) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Invalid request token.'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $reservation_id = intval($_POST['reservation_id'] ?? 0);
        $new_status = trim($_POST['status'] ?? '');

        if ($reservation_id <= 0 || !in_array($new_status, ['pending', 'confirmed', 'cancelled'], true)) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Invalid input.'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        // Update status in database
        $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $reservation_id);
            if ($stmt->execute()) {
                $_SESSION['admin_notice'] = ['type' => 'success', 'text' => "Status updated for reservation #$reservation_id."];
            } else {
                $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Database error: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Prepare statement failed: ' . $conn->error];
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch reservations (latest first) - limit to 200 for safety
$stmt = $conn->prepare("SELECT id, booking_id, name, email, phone, date, time, people, message, created_at, status FROM reservations ORDER BY created_at DESC LIMIT 200");
if (!$stmt) {
    die("DB prepare failed: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$reservations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>View Reservations</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root{
  --primary:#2d3748; --light:#f7fafc; --lighter:#fff;
  --border:#e2e8f0; --radius:.375rem; --shadow:0 1px 3px rgba(0,0,0,.08);
  --success:#38a169; --danger:#e53e3e; --warning:#dd6b20;
  --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
*{box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:var(--light);color:var(--primary);padding:2rem}
.container{max-width:1200px;margin:0 auto}
.header{display:flex;flex-direction:column;align-items:center;margin-bottom:1.5rem;position:relative}
.header h1{font-size:1.8rem;margin-bottom:1rem;animation:fadeIn 0.5s ease-out;text-align:center}
.header-actions{display:flex;gap:1rem;align-items:center}
.card{background:var(--lighter);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;transform:translateY(0);transition:var(--transition)}
.card:hover{transform:translateY(-3px);box-shadow:0 10px 20px rgba(0,0,0,0.1)}
.table-container{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{background:var(--primary);color:#fff;padding:0.75rem;text-align:left;font-size:.85rem}
td{padding:0.75rem;border-bottom:1px solid var(--border);vertical-align:top;font-size:.9rem;transition:var(--transition)}
tr{transition:var(--transition)}
tr:hover{transform:translateX(5px)}
tr:hover td{background:rgba(237,242,247,0.7)}
.status{display:inline-block;padding:.25rem .5rem;border-radius:var(--radius);font-size:.75rem;text-transform:capitalize;transition:var(--transition)}
.status:hover{transform:scale(1.05);box-shadow:0 2px 5px rgba(0,0,0,0.1)}
.status-pending{background:rgba(221,107,32,0.1);color:var(--warning)}
.status-confirmed{background:rgba(56,161,105,0.1);color:var(--success)}
.status-cancelled{background:rgba(229,62,62,0.1);color:var(--danger)}
.form-inline{display:flex;gap:.5rem;align-items:center}
select{padding:.4rem;border-radius:var(--radius);border:1px solid var(--border);min-width:130px;transition:var(--transition)}
select:focus{outline:none;box-shadow:0 0 0 2px rgba(66,153,225,0.5);transform:scale(1.02)}
.btn{padding:.45rem .75rem;border-radius:var(--radius);border:none;cursor:pointer;font-weight:500;transition:var(--transition);display:inline-flex;align-items:center;gap:0.5rem}
.btn:hover{transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,0.1)}
.btn:active{transform:translateY(0)}
.btn-sm{font-size:.8rem;padding:.3rem .5rem}
.btn-update{background:#2b6cb0;color:#fff}
.btn-update:hover{background:#2c5282}
.btn-refresh{background:#4a5568;color:#fff}
.btn-refresh:hover{background:#2d3748}
.note{padding:0.85rem;margin-bottom:1rem;border-radius:var(--radius);animation:fadeIn 0.5s ease-out}
.note.success{background:rgba(56,161,105,0.08);border-left:4px solid var(--success);color:var(--success)}
.note.error{background:rgba(229,62,62,0.06);border-left:4px solid var(--danger);color:var(--danger)}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse{0%{transform:scale(1)}50%{transform:scale(1.05)}100%{transform:scale(1)}}
@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
.loading{animation:pulse 1.5s infinite}
.spin{animation:spin 1s linear infinite}
.refresh-countdown{position:absolute;right:0;top:0;background:rgba(74,85,104,0.1);padding:0.25rem 0.5rem;border-radius:var(--radius);font-size:0.8rem;color:var(--primary)}
@media (max-width:768px){body{padding:1rem}th,td{padding:.5rem}.header-actions{flex-direction:column;gap:0.5rem}.refresh-countdown{position:static;margin-top:0.5rem}}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Reservations Management</h1>
    <div class="header-actions">
      <button id="refreshBtn" class="btn btn-refresh">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
      <div id="refreshCountdown" class="refresh-countdown">Refreshing in 8s</div>
    </div>
  </div>

  <?php if (!empty($_SESSION['admin_notice'])):
    $n = $_SESSION['admin_notice'];
    $cls = ($n['type'] === 'success') ? 'success' : 'error';
  ?>
    <div class="note <?php echo $cls; ?>">
      <?php echo htmlspecialchars($n['text']); ?>
    </div>
  <?php unset($_SESSION['admin_notice']); endif; ?>

  <div class="card">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Booking ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Date / Time</th>
            <th>People</th>
            <th>Message</th>
            <th>Created</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($reservations)): ?>
            <tr><td colspan="11" style="text-align:center;padding:2rem;color:#718096">No reservations found.</td></tr>
          <?php else: foreach ($reservations as $r): ?>
            <tr>
              <td><?php echo (int)$r['id']; ?></td>
              <td><?php echo htmlspecialchars($r['booking_id']); ?></td>
              <td><?php echo htmlspecialchars($r['name']); ?></td>
              <td><?php echo htmlspecialchars($r['email']); ?></td>
              <td><?php echo htmlspecialchars($r['phone']); ?></td>
              <td><?php echo htmlspecialchars(date('M j, Y', strtotime($r['date'])) . ' — ' . date('g:i A', strtotime($r['time']))); ?></td>
              <td><?php echo (int)$r['people']; ?></td>
              <td style="max-width:240px;white-space:pre-wrap;word-break:break-word"><?php echo htmlspecialchars($r['message']); ?></td>
              <td><?php echo htmlspecialchars(date("M j, Y g:i A", strtotime($r['created_at']))); ?></td>
              <td>
                <span class="status status-<?php echo strtolower($r['status']); ?>">
                  <?php echo ucfirst($r['status']); ?>
                </span>
              </td>
              <td>
                <form method="post" class="form-inline">
                  <input type="hidden" name="action" value="update_status" />
                  <input type="hidden" name="reservation_id" value="<?php echo (int)$r['id']; ?>" />
                  <input type="hidden" name="admin_token" value="<?php echo $admin_token; ?>" />
                  <select name="status" aria-label="Change status">
                    <option value="pending" <?php if ($r['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="confirmed" <?php if ($r['status'] === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="cancelled" <?php if ($r['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                  </select>
                  <button type="submit" class="btn btn-sm btn-update">Update</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Refresh functionality
  const refreshBtn = document.getElementById('refreshBtn');
  const countdownElement = document.getElementById('refreshCountdown');
  let countdown = 8;
  let refreshInterval;
  
  // Start the countdown timer
  function startCountdown() {
    countdown = 8;
    updateCountdown();
    refreshInterval = setInterval(() => {
      countdown--;
      updateCountdown();
      if (countdown <= 0) {
        clearInterval(refreshInterval);
        refreshPage();
      }
    }, 1000);
  }
  
  // Update countdown display
  function updateCountdown() {
    countdownElement.textContent = `Refreshing in ${countdown}s`;
  }
  
  // Refresh the page
  function refreshPage() {
    window.location.reload();
  }
  
  // Manual refresh button
  refreshBtn.addEventListener('click', function() {
    // Add spin animation
    const icon = this.querySelector('i');
    icon.classList.add('spin');
    
    // Refresh after animation completes
    setTimeout(() => {
      refreshPage();
    }, 1000);
  });
  
  // Start the initial countdown
  startCountdown();
  
  // Reset countdown when page gains focus (in case user was away)
  window.addEventListener('focus', function() {
    clearInterval(refreshInterval);
    startCountdown();
  });

  // Enhanced animations and interactions
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
      const btn = this.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch spin"></i> Updating';
      }
    });
  });

  // Table row hover effects
  const rows = document.querySelectorAll('tbody tr');
  rows.forEach(row => {
    row.addEventListener('mouseenter', function() {
      this.style.transform = 'translateX(5px)';
    });
    row.addEventListener('mouseleave', function() {
      this.style.transform = 'translateX(0)';
    });
  });

  // Status badge hover effects
  const statusBadges = document.querySelectorAll('.status');
  statusBadges.forEach(badge => {
    badge.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.05)';
    });
    badge.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  });

  // Select focus effects
  const selects = document.querySelectorAll('select');
  selects.forEach(select => {
    select.addEventListener('focus', function() {
      this.style.transform = 'scale(1.02)';
      this.style.boxShadow = '0 0 0 2px rgba(66,153,225,0.5)';
    });
    select.addEventListener('blur', function() {
      this.style.transform = 'scale(1)';
      this.style.boxShadow = 'none';
    });
  });
});
</script>
</body>
</html>