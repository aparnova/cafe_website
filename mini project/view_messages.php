<?php
include 'db.php';
session_start();

// CSRF token for status updates
if (empty($_SESSION['admin_token'])) {
    $_SESSION['admin_token'] = bin2hex(random_bytes(24));
}
$admin_token = $_SESSION['admin_token'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        // CSRF check
        $token = $_POST['admin_token'] ?? '';
        if (!hash_equals($_SESSION['admin_token'], $token)) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Invalid request token.'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $message_id = intval($_POST['id'] ?? 0);
        $new_status = trim($_POST['status'] ?? '');

        if ($message_id <= 0 || !in_array($new_status, ['read', 'unread'], true)) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Invalid input.'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        // Update status in database
        $stmt = $conn->prepare("UPDATE contact_submissions SET status = ? WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $message_id);
            if ($stmt->execute()) {
                $_SESSION['admin_notice'] = ['type' => 'success', 'text' => "Status updated for message #$message_id."];
            } else {
                $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Database error: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Prepare statement failed: ' . $conn->error];
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// If AJAX refresh requested, return only table rows (used by JS)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $stmt = $conn->prepare("SELECT id, name, email, phone, message, submission_date, status FROM contact_submissions ORDER BY submission_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rowId = (int)$row['id'];
            $status = $row['status'];
            $trClass = ($status === 'unread') ? 'unread' : '';
            $btnClass = ($status === 'unread') ? 'mark-read' : 'mark-unread';
            $btnText = ($status === 'unread') ? 'Mark as Read' : 'Mark as Unread';
            $nextStatus = ($status === 'unread') ? 'read' : 'unread';
            echo "<tr data-id=\"{$rowId}\" class=\"{$trClass}\">";
            echo "<td>{$rowId}</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
            echo "<td style=\"max-width:400px;white-space:pre-wrap;\">" . htmlspecialchars($row['message']) . "</td>";
            echo "<td>" . htmlspecialchars($row['submission_date']) . "</td>";
            echo "<td>";
            echo "<form method=\"post\" class=\"form-inline\">";
            echo "<input type=\"hidden\" name=\"action\" value=\"toggle_status\" />";
            echo "<input type=\"hidden\" name=\"id\" value=\"{$rowId}\" />";
            echo "<input type=\"hidden\" name=\"admin_token\" value=\"{$admin_token}\" />";
            echo "<input type=\"hidden\" name=\"status\" value=\"{$nextStatus}\" />";
            echo "<button type=\"submit\" class=\"btn btn-sm {$btnClass}\">{$btnText}</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="7" style="text-align:center;padding:1.5rem;color:#6c757d">No messages found.</td></tr>';
    }
    exit;
}

// Fetch messages (latest first) - limit to 200 for safety
$stmt = $conn->prepare("SELECT id, name, email, phone, message, submission_date, status FROM contact_submissions ORDER BY submission_date DESC LIMIT 200");
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Contact Messages</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root{
  --primary:#2d3748; --light:#f7fafc; --lighter:#fff;
  --border:#e2e8f0; --radius:.375rem; --shadow:0 1px 3px rgba(0,0,0,.08);
  --success:#38a169; --danger:#e53e3e; --warning:#dd6b20; --info:#3182ce;
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
.unread{font-weight:600;background:rgba(221,107,32,0.05)}
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
.btn-mark-read{background:var(--success);color:#fff}
.btn-mark-read:hover{background:#2f855a}
.btn-mark-unread{background:var(--warning);color:#fff}
.btn-mark-unread:hover{background:#c05621}
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
    <h1>Contact Messages</h1>
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
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Message</th>
            <th>Submitted At</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="messagesBody">
          <?php if (empty($messages)): ?>
            <tr><td colspan="7" style="text-align:center;padding:2rem;color:#718096">No messages found.</td></tr>
          <?php else: foreach ($messages as $m): ?>
            <tr data-id="<?php echo (int)$m['id']; ?>" class="<?php echo $m['status'] === 'unread' ? 'unread' : ''; ?>">
              <td><?php echo (int)$m['id']; ?></td>
              <td><?php echo htmlspecialchars($m['name']); ?></td>
              <td><?php echo htmlspecialchars($m['email']); ?></td>
              <td><?php echo htmlspecialchars($m['phone']); ?></td>
              <td style="max-width:400px;white-space:pre-wrap;word-break:break-word"><?php echo htmlspecialchars($m['message']); ?></td>
              <td><?php echo htmlspecialchars($m['submission_date']); ?></td>
              <td>
                <form method="post" class="form-inline">
                  <input type="hidden" name="action" value="toggle_status" />
                  <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>" />
                  <input type="hidden" name="admin_token" value="<?php echo $admin_token; ?>" />
                  <input type="hidden" name="status" value="<?php echo $m['status'] === 'unread' ? 'read' : 'unread'; ?>" />
                  <button type="submit" class="btn btn-sm <?php echo $m['status'] === 'unread' ? 'btn-mark-read' : 'btn-mark-unread'; ?>">
                    <?php echo $m['status'] === 'unread' ? 'Mark as Read' : 'Mark as Unread'; ?>
                  </button>
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
  
  // AJAX refresh function
  async function doRefresh() {
    try {
      const resp = await fetch(window.location.pathname + '?ajax=1', {cache: 'no-cache'});
      if (!resp.ok) throw new Error('Network error: ' + resp.status);
      const html = await resp.text();
      document.getElementById('messagesBody').innerHTML = html;
    } catch (err) {
      console.error('Refresh failed:', err);
    }
  }
  
  // Refresh the page
  function refreshPage() {
    doRefresh();
    startCountdown();
  }
  
  // Manual refresh button
  refreshBtn.addEventListener('click', function() {
    const icon = this.querySelector('i');
    icon.classList.add('spin');
    
    setTimeout(() => {
      refreshPage();
      icon.classList.remove('spin');
    }, 1000);
  });
  
  // Start the initial countdown
  startCountdown();
  
  // Reset countdown when page gains focus
  window.addEventListener('focus', function() {
    clearInterval(refreshInterval);
    startCountdown();
  });

  // Form submission handling
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

  // Button hover effects
  const buttons = document.querySelectorAll('.btn');
  buttons.forEach(button => {
    button.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-2px)';
    });
    button.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });
});
</script>
</body>
</html>