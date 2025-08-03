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
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      background: #f9f9f9;
      color: #333;
    }
    .container {
      max-width: 1000px;
      margin: 60px auto;
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    h2 {
      margin-bottom: 20px;
      color: #111827;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table th, table td {
      padding: 12px 15px;
      border: 1px solid #ddd;
      text-align: left;
    }
    table th {
      background: #111827;
      color: white;
    }
    table tr:nth-child(even) {
      background: #f3f4f6;
    }
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      background: #111827;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      transition: background 0.3s ease;
    }
    .back-btn:hover {
      background: #374151;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Registered Customers</h2>
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
          while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
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

    <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>
</body>
</html>
