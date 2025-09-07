<?php
session_start();
require 'db.php';

// --- Authentication check ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Handle Add Item ---
if (isset($_POST['add_item'])) {
    $stmt = $conn->prepare("INSERT INTO menu_items (name, category, price, description, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $_POST['name'], $_POST['category'], $_POST['price'], $_POST['description'], $_POST['image']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['admin_notice'] = ['type' => 'success', 'text' => 'Menu item added successfully.'];
    header("Location: manage_menu.php");
    exit();
}

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM menu_items WHERE id=$id");
    $_SESSION['admin_notice'] = ['type' => 'success', 'text' => 'Menu item deleted.'];
    header("Location: manage_menu.php");
    exit();
}

// --- Handle Edit (fetch) ---
$editItem = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM menu_items WHERE id=$id");
    $editItem = $res->fetch_assoc();
}

// --- Handle Update ---
if (isset($_POST['update_item'])) {
    $stmt = $conn->prepare("UPDATE menu_items SET name=?, category=?, price=?, description=?, image=? WHERE id=?");
    $stmt->bind_param("ssissi", $_POST['name'], $_POST['category'], $_POST['price'], $_POST['description'], $_POST['image'], $_POST['id']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['admin_notice'] = ['type' => 'success', 'text' => 'Menu item updated successfully.'];
    header("Location: manage_menu.php");
    exit();
}

// --- Fetch All Items ---
$result = $conn->query("
    SELECT * FROM menu_items 
    ORDER BY FIELD(category, 'starters','main','desserts','beverages'), id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Manage Menu</title>
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
.container{max-width:1200px;margin:0 auto;position:relative}
.header{text-align:center;margin-bottom:1.5rem}
.header h1{font-size:1.8rem;margin-bottom:1rem;animation:fadeIn 0.5s ease-out}
.back-btn{
  display:inline-flex;
  align-items:center;
  gap:0.5rem;
  padding:0.5rem 1rem;
  background:#6366f1;
  color:#fff;
  text-decoration:none;
  border-radius:var(--radius);
  transition:var(--transition);
  font-weight:500;
  position:absolute;
  top:0;
  right:0;
  z-index:10;
}
.back-btn:hover{
  background:#4f46e5;
  transform:translateX(2px);
}
.card{background:var(--lighter);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;transition:var(--transition);padding:1rem;margin-bottom:1.5rem}
.card:hover{transform:translateY(-3px);box-shadow:0 10px 20px rgba(0,0,0,0.1)}
table{width:100%;border-collapse:collapse}
th{background:var(--primary);color:#fff;padding:0.75rem;text-align:left;font-size:.85rem}
td{padding:0.75rem;border-bottom:1px solid var(--border);vertical-align:top;font-size:.9rem;transition:var(--transition)}
tr:hover td{background:rgba(237,242,247,0.7)}
.btn{padding:.45rem .75rem;border-radius:var(--radius);border:none;cursor:pointer;font-weight:500;transition:var(--transition);display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none}
.btn-sm{font-size:.8rem;padding:.3rem .5rem}
.btn-add{background:#38a169;color:#fff}
.btn-add:hover{background:#2f855a}
.btn-edit{background:#2b6cb0;color:#fff}
.btn-edit:hover{background:#2c5282}
.btn-del{background:#e53e3e;color:#fff}
.btn-del:hover{background:#c53030}
input,select,textarea{width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:0.8rem}
.note{padding:0.85rem;margin-bottom:1rem;border-radius:var(--radius);animation:fadeIn 0.5s ease-out}
.note.success{background:rgba(56,161,105,0.08);border-left:4px solid var(--success);color:var(--success)}
.note.error{background:rgba(229,62,62,0.06);border-left:4px solid var(--danger);color:var(--danger)}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body>
<div class="container">
  <!-- Back to Dashboard Button -->
  <a href="admin_dashboard.php" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    Back to Dashboard
  </a>

  <div class="header">
    <h1>Menu Management</h1>
  </div>

  <?php if (!empty($_SESSION['admin_notice'])):
    $n = $_SESSION['admin_notice'];
    $cls = ($n['type'] === 'success') ? 'success' : 'error'; ?>
    <div class="note <?php echo $cls; ?>">
      <?php echo htmlspecialchars($n['text']); ?>
    </div>
  <?php unset($_SESSION['admin_notice']); endif; ?>

  <div class="card">
    <h2><?php echo $editItem ? "Edit Item" : "Add New Item"; ?></h2>
    <form method="POST">
      <?php if ($editItem): ?>
        <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
      <?php endif; ?>
      <input type="text" name="name" placeholder="Item Name" value="<?php echo $editItem['name'] ?? ''; ?>" required>
      <select name="category" required>
        <option value="">Select Category</option>
        <option value="starters" <?php if (($editItem['category'] ?? '')==='starters') echo 'selected'; ?>>Starters</option>
        <option value="main" <?php if (($editItem['category'] ?? '')==='main') echo 'selected'; ?>>Main Course</option>
        <option value="desserts" <?php if (($editItem['category'] ?? '')==='desserts') echo 'selected'; ?>>Desserts</option>
        <option value="beverages" <?php if (($editItem['category'] ?? '')==='beverages') echo 'selected'; ?>>Beverages</option>
      </select>
      <input type="number" name="price" placeholder="Price (₹)" value="<?php echo $editItem['price'] ?? ''; ?>" required>
      <textarea name="description" placeholder="Description"><?php echo $editItem['description'] ?? ''; ?></textarea>
      <input type="text" name="image" placeholder="Image URL" value="<?php echo $editItem['image'] ?? ''; ?>">
      <button type="submit" name="<?php echo $editItem ? 'update_item' : 'add_item'; ?>" class="btn btn-add">
        <?php echo $editItem ? 'Update Item' : 'Add Item'; ?>
      </button>
      <?php if ($editItem): ?>
        <a href="manage_menu.php" class="btn btn-del">Cancel</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h2>Menu Items</h2>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Description</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:#718096">No menu items found.</td></tr>
        <?php else: while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo ucfirst($row['category']); ?></td>
            <td>₹<?php echo $row['price']; ?></td>
            <td><?php echo htmlspecialchars($row['description']); ?></td>
            <td><img src="<?php echo $row['image']; ?>" alt="img" width="60"></td>
            <td>
              <a href="manage_menu.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">Edit</a>
              <a href="manage_menu.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-del" onclick="return confirm('Delete this item?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>