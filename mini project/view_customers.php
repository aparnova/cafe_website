<?php
include 'db.php';
session_start();

// CSRF token for potential future actions
if (empty($_SESSION['admin_token'])) {
    $_SESSION['admin_token'] = bin2hex(random_bytes(24));
}
$admin_token = $_SESSION['admin_token'];

// Fetch customers (latest first) - limit to 200 for safety
$stmt = $conn->prepare("SELECT id, fullname, email, phone, created_at FROM users ORDER BY created_at DESC LIMIT 200");
if (!$stmt) {
    die("DB prepare failed: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$customers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Registered Customers - Admin Dashboard</title>
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
.btn{padding:.45rem .75rem;border-radius:var(--radius);border:none;cursor:pointer;font-weight:500;transition:var(--transition);display:inline-flex;align-items:center;gap:0.5rem}
.btn:hover{transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,0.1)}
.btn:active{transform:translateY(0)}
.btn-back{background:#4a5568;color:#fff;text-decoration:none}
.btn-back:hover{background:#2d3748}
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
    <h1>Registered Customers</h1>
    <div class="header-actions">
      <button id="refreshBtn" class="btn btn-refresh">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
      <div id="refreshCountdown" class="refresh-countdown">Refreshing in 8s</div>
    </div>
  </div>

  <div class="card">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Registered At</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr><td colspan="5" style="text-align:center;padding:2rem;color:#718096">No customers found.</td></tr>
          <?php else: foreach ($customers as $customer): ?>
            <tr>
              <td><?php echo (int)$customer['id']; ?></td>
              <td><?php echo htmlspecialchars($customer['fullname']); ?></td>
              <td><?php echo htmlspecialchars($customer['email']); ?></td>
              <td><?php echo htmlspecialchars($customer['phone']); ?></td>
              <td><?php echo htmlspecialchars(date("M j, Y g:i A", strtotime($customer['created_at']))); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <a href="admin_dashboard.php" class="btn btn-back" style="margin-top:20px;">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
  </a>
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
});
</script>
</body>
</html>