<?php
include 'db.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Messages - Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #343a40;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .unread {
            font-weight: bold;
            background-color: #fff9e6;
        }
        .status-btn {
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            color: white;
            border-radius: 5px;
        }
        .mark-read {
            background-color: green;
        }
        .mark-unread {
            background-color: orange;
        }
    </style>
</head>
<body>

<h2>Customer Contact Messages</h2>

<?php
$sql = "SELECT * FROM contact_submissions";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Message</th>
            <th>Submitted At</th>
            <th>Status</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr class="<?= $row['status'] === 'unread' ? 'unread' : '' ?>">
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['message']) ?></td>
                <td><?= $row['submission_date'] ?></td>
                <td>
                    <button class="status-btn <?= $row['status'] === 'unread' ? 'mark-read' : 'mark-unread' ?>" 
                        onclick="toggleStatus(<?= $row['id'] ?>, '<?= $row['status'] === 'unread' ? 'read' : 'unread' ?>')">
                        Mark as <?= $row['status'] === 'unread' ? 'Read' : 'Unread' ?>
                    </button>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p style="text-align: center;">No messages found.</p>
<?php endif; ?>

<script>
function toggleStatus(id, newStatus) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "toggle_message_status.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        if (xhr.status === 200 && xhr.responseText === "success") {
            location.reload(); // reload to update styling/status
        } else {
            alert("Failed to update status.");
        }
    };
    xhr.send(`id=${id}&status=${newStatus}`);
}
</script>

</body>
</html>
