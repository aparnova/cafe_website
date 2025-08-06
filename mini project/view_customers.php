<?php
include 'db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registered Customers - Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <style>
    :root {
      --primary-color: #111827;
      --secondary-color: #374151;
      --accent-color: #4f46e5;
      --light-bg: #f9f9f9;
      --table-even: #f3f4f6;
      --table-hover: #e5e7eb;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      background: var(--light-bg);
      color: #333;
      transition: all 0.3s ease;
    }
    
    .container {
      max-width: 1000px;
      margin: 60px auto;
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      transform: translateY(-20px);
      opacity: 0;
      animation: fadeInUp 0.6s ease-out forwards;
    }
    
    h2 {
      margin-bottom: 20px;
      color: var(--primary-color);
      position: relative;
      display: inline-block;
    }
    
    h2::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 60px;
      height: 4px;
      background: var(--accent-color);
      border-radius: 2px;
      transition: width 0.3s ease;
    }
    
    h2:hover::after {
      width: 100px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      overflow: hidden;
      border-radius: 8px;
    }
    
    table th, table td {
      padding: 15px 20px;
      border: 1px solid #ddd;
      text-align: left;
      transition: all 0.2s ease;
    }
    
    table th {
      background: var(--primary-color);
      color: white;
      font-weight: 500;
      letter-spacing: 0.5px;
    }
    
    table tr:nth-child(even) {
      background: var(--table-even);
    }
    
    table tr:hover td {
      background: var(--table-hover);
      transform: translateX(4px);
    }
    
    table tr {
      transition: all 0.3s ease;
    }
    
    table tr:first-child:hover td {
      transform: none;
      background: var(--primary-color);
    }
    
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 30px;
      padding: 12px 24px;
      background: var(--primary-color);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .back-btn:hover {
      background: var(--accent-color);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
    }
    
    .back-btn:active {
      transform: translateY(0);
    }
    
    .loading-row {
      animation: pulse 1.5s infinite ease-in-out;
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes pulse {
      0% { opacity: 0.6; }
      50% { opacity: 1; }
      100% { opacity: 0.6; }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    /* Responsive table */
    @media (max-width: 768px) {
      .container {
        padding: 20px;
        margin: 20px;
      }
      
      table {
        display: block;
        overflow-x: auto;
      }
    }
    
    /* Notification style */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      background: var(--accent-color);
      color: white;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transform: translateX(200%);
      transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      z-index: 1000;
    }
    
    .notification.show {
      transform: translateX(0);
    }
    
    /* Floating action button */
    .fab {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 60px;
      height: 60px;
      background: var(--accent-color);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 100;
    }
    
    .fab:hover {
      transform: scale(1.1) translateY(-5px);
      box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
    }
  </style>
</head>
<body>
  <!-- Notification element -->
  <div id="notification" class="notification" style="display: none;">
    <i class="fas fa-check-circle"></i> Action completed successfully!
  </div>
  
  <!-- Floating action button -->
  <div class="fab animate__animated animate__bounceInUp" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
  </div>

  <div class="container">
    <h2 class="animate__animated animate__fadeIn">Registered Customers</h2>
    
    <div class="table-responsive">
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
          <?php
          $query = "SELECT * FROM users ORDER BY created_at ASC";
          $result = mysqli_query($conn, $query);

          if ($result && mysqli_num_rows($result) > 0) {
            $count = 0;
            while ($row = mysqli_fetch_assoc($result)) {
              $count++;
              $animationDelay = $count * 0.1;
              echo "<tr class='animate__animated animate__fadeInUp' style='animation-delay: {$animationDelay}s'>";
              echo "<td>" . htmlspecialchars($row['id']) . "</td>";
              echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
              echo "<td>" . htmlspecialchars($row['email']) . "</td>";
              echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
              echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='5'>No customers found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <a href="admin_dashboard.php" class="back-btn animate__animated animate__fadeIn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>

  <script>
    // Show notification
    function showNotification(message) {
      const notification = document.getElementById('notification');
      notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
      notification.style.display = 'block';
      
      setTimeout(() => {
        notification.classList.add('show');
      }, 10);
      
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
          notification.style.display = 'none';
        }, 400);
      }, 3000);
    }
    
    // Scroll to top function
    function scrollToTop() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }
    
    // Show FAB when scrolling
    window.addEventListener('scroll', function() {
      const fab = document.querySelector('.fab');
      if (window.scrollY > 300) {
        fab.style.display = 'flex';
      } else {
        fab.style.display = 'none';
      }
    });
    
    // Table row click effect
    document.addEventListener('DOMContentLoaded', function() {
      const rows = document.querySelectorAll('tbody tr');
      
      rows.forEach(row => {
        row.addEventListener('click', function() {
          // Remove active class from all rows
          rows.forEach(r => r.classList.remove('active-row'));
          
          // Add active class to clicked row
          this.classList.add('active-row');
          
          // Show notification (simulate action)
          showNotification('Customer selected');
        });
      });
      
      // Simulate loading data
      setTimeout(() => {
        showNotification('Data loaded successfully');
      }, 1000);
    });
  </script>
</body>
</html>