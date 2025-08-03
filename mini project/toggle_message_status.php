<?php
include 'db.php';

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $newStatus = ($_POST['status'] === 'read') ? 'read' : 'unread';

    $stmt = $conn->prepare("UPDATE contact_submissions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $id);
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
