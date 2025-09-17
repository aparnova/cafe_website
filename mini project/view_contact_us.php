<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "westleys_resto_cafe";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle maps URL update
    if (isset($_POST['update_maps'])) {
        $maps_url = $conn->real_escape_string($_POST['maps_url']);
        
        // Check if a record already exists
        $check_sql = "SELECT id FROM contact_settings LIMIT 1";
        $result = $conn->query($check_sql);
        
        if ($result->num_rows > 0) {
            $update_sql = "UPDATE contact_settings SET maps_url = '$maps_url'";
        } else {
            $update_sql = "INSERT INTO contact_settings (maps_url) VALUES ('$maps_url')";
        }
        
        if ($conn->query($update_sql)) {
            $message = "Maps URL updated successfully!";
        } else {
            $error = "Error updating maps URL: " . $conn->error;
        }
    }
    
    // Handle contact info addition/update
    if (isset($_POST['save_contact_info'])) {
        $icon_class = $conn->real_escape_string($_POST['icon_class']);
        $title = $conn->real_escape_string($_POST['title']);
        $content = $conn->real_escape_string($_POST['content']);
        $display_order = intval($_POST['display_order']);
        
        if ($id > 0) {
            // Update existing record
            $sql = "UPDATE contact_info SET icon_class = '$icon_class', title = '$title', 
                    content = '$content', display_order = $display_order WHERE id = $id";
        } else {
            // Insert new record
            $sql = "INSERT INTO contact_info (icon_class, title, content, display_order) 
                    VALUES ('$icon_class', '$title', '$content', $display_order)";
        }
        
        if ($conn->query($sql)) {
            $message = $id > 0 ? "Contact info updated successfully!" : "Contact info added successfully!";
            $id = 0; // Reset ID after successful operation
        } else {
            $error = "Error saving contact info: " . $conn->error;
        }
    }
}

// Handle delete action
if ($action == 'delete' && $id > 0) {
    $sql = "DELETE FROM contact_info WHERE id = $id";
    if ($conn->query($sql)) {
        $message = "Contact info deleted successfully!";
    } else {
        $error = "Error deleting contact info: " . $conn->error;
    }
}

// Fetch current maps URL
$maps_url = '';
$maps_result = $conn->query("SELECT maps_url FROM contact_settings LIMIT 1");
if ($maps_result->num_rows > 0) {
    $maps_row = $maps_result->fetch_assoc();
    $maps_url = $maps_row['maps_url'];
}

// Fetch contact info items for editing
$edit_item = null;
if ($action == 'edit' && $id > 0) {
    $result = $conn->query("SELECT * FROM contact_info WHERE id = $id");
    if ($result->num_rows > 0) {
        $edit_item = $result->fetch_assoc();
    }
}

// Fetch all contact info items
$contact_items = [];
$result = $conn->query("SELECT * FROM contact_info ORDER BY display_order, id");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $contact_items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contact Information - Westley's Resto Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
    :root{
      --primary:#2d3748; --light:#f7fafc; --lighter:#fff;
      --border:#e2e8f0; --radius:.375rem; --shadow:0 1px 3px rgba(0,0,0,.08);
      --success:#38a169; --danger:#e53e3e; --warning:#dd6b20;
      --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      --accent-color: #cda45e; /* Preserving the accent color from original */
    }
    *{box-sizing:border-box}
    body{font-family:'Inter',sans-serif;background:var(--light);color:var(--primary);padding:2rem}
    .container{max-width:1200px;margin:0 auto}
    .header{display:flex;flex-direction:column;align-items:center;margin-bottom:1.5rem;position:relative}
    .header h1{font-size:1.8rem;margin-bottom:1rem;animation:fadeIn 0.5s ease-out;text-align:center}
    
    /* Back to Dashboard Button */
    .back-to-dashboard {
        position: absolute;
        top: 0;
        right: 0;
        background-color: var(--accent-color);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: var(--radius);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Inter', sans-serif;
    }
    .back-to-dashboard:hover {
        background-color: #b8935a;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        color: white;
        text-decoration: none;
    }
    
    /* Cards and Forms */
    .card {
        background: var(--lighter);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        transform: translateY(0);
        transition: var(--transition);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .form-group {
        margin-bottom: 1rem;
    }
    label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    input[type="text"], 
    input[type="url"], 
    textarea,
    input[type="number"] {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        font-family: 'Inter', sans-serif;
        transition: var(--transition);
    }
    input[type="text"]:focus, 
    input[type="url"]:focus, 
    textarea:focus,
    input[type="number"]:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(66,153,225,0.5);
        border-color: #4299e1;
    }
    textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    /* Buttons (general) */
    .btn {
        padding: 0.5rem 1rem;
        border-radius: var(--radius);
        border: none;
        cursor: pointer;
        font-weight: 500;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Inter', sans-serif;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    /* Item Lists */
    .item-list {
        margin-top: 1.5rem;
    }
    .item-card {
        background: var(--lighter);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        transition: var(--transition);
    }
    .item-card:hover {
        transform: translateX(5px);
    }
    
    /* Alerts */
    .message {
        padding: 1rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        animation: fadeIn 0.5s ease-out;
    }
    .success {
        background: rgba(56,161,105,0.08);
        border-left: 4px solid var(--success);
        color: var(--success);
    }
    .error {
        background: rgba(229,62,62,0.06);
        border-left: 4px solid var(--danger);
        color: var(--danger);
    }
    
    /* Edit/Delete Buttons (preserved from original) */
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        background-color: #bb2d3b;
    }
    .btn-edit {
        background-color: #17a2b8;
        color: white;
    }
    .btn-edit:hover {
        background-color: #138496;
    }
    
    /* Preview section styles */
    .preview-section {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }
    .preview-container {
        background: var(--lighter);
        padding: 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 30px;
    }
    .info-item i {
        color: white;
        background: var(--accent-color);
        font-size: 20px;
        width: 44px;
        height: 44px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 4px;
        flex-shrink: 0;
    }
    .info-item-content h3 {
        margin: 0 0 8px 0;
        font-size: 18px;
    }
    .info-item-content p {
        margin: 0;
        line-height: 1.6;
    }
    .maps-preview {
        width: 100%;
        height: 300px;
        border: 0;
        border-radius: var(--radius);
        margin-bottom: 20px;
    }
    
    /* Table styles */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }
    th {
        font-weight: 600;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px) }
        to { opacity: 1; transform: translateY(0) }
    }
    
    @media (max-width: 768px) {
        body { padding: 1rem }
        .item-card { flex-direction: column }
        .back-to-dashboard {
            position: static;
            margin-bottom: 1rem;
            align-self: flex-end;
        }
        .header {
            align-items: flex-start;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admin_dashboard.php" class="back-to-dashboard">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <h1>Manage Contact Information</h1>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message success">
                <i class="bi bi-check-circle-fill"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Google Maps Settings</h2>
            <form method="post">
                <div class="form-group">
                    <label for="maps_url">Google Maps Embed URL</label>
                    <input type="url" id="maps_url" name="maps_url" value="<?php echo htmlspecialchars($maps_url); ?>" required>
                </div>
                <button type="submit" name="update_maps" class="btn" style="background: var(--primary); color: white;">Update Maps URL</button>
            </form>
        </div>

        <div class="card">
            <h2><?php echo $id > 0 ? 'Edit Contact Information' : 'Add Contact Information'; ?></h2>
            <form method="post">
                <div class="form-group">
                    <label for="icon_class">Icon Class (Bootstrap Icons)</label>
                    <input type="text" id="icon_class" name="icon_class" 
                           value="<?php echo $edit_item ? htmlspecialchars($edit_item['icon_class']) : ''; ?>" 
                           placeholder="bi bi-geo-alt" required>
                    <small>Use Bootstrap Icons class names (e.g., bi bi-geo-alt, bi bi-clock, etc.)</small>
                </div>
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo $edit_item ? htmlspecialchars($edit_item['title']) : ''; ?>" 
                           placeholder="Location" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="3" required><?php 
                        echo $edit_item ? htmlspecialchars($edit_item['content']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" 
                           value="<?php echo $edit_item ? $edit_item['display_order'] : '0'; ?>" min="0">
                </div>
                
                <button type="submit" name="save_contact_info" class="btn" style="background: var(--primary); color: white;">
                    <?php echo $id > 0 ? 'Update' : 'Add'; ?> Contact Info
                </button>
                
                <?php if ($id > 0): ?>
                    <a href="view_contact_us.php" class="btn" style="background: #6c757d; color: white;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>Current Contact Information</h2>
            
            <?php if (count($contact_items) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contact_items as $item): ?>
                            <tr>
                                <td><i class="<?php echo htmlspecialchars($item['icon_class']); ?>"></i></td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['content']); ?></td>
                                <td><?php echo $item['display_order']; ?></td>
                                <td>
                                    <a href="view_contact_us.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-edit">Edit</a>
                                    <a href="view_contact_us.php?action=delete&id=<?php echo $item['id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No contact information added yet.</p>
            <?php endif; ?>
        </div>

        <div class="preview-section">
            <h2>Preview</h2>
            <p>This is how your contact information will appear on the frontend:</p>
            
            <div class="preview-container">
                <h3>Google Maps Preview</h3>
                <?php if (!empty($maps_url)): ?>
                    <iframe class="maps-preview" src="<?php echo htmlspecialchars($maps_url); ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <?php else: ?>
                    <p>No maps URL configured.</p>
                <?php endif; ?>
                
                <h3>Contact Information Preview</h3>
                <?php if (count($contact_items) > 0): ?>
                    <?php foreach ($contact_items as $item): ?>
                        <div class="info-item">
                            <i class="<?php echo htmlspecialchars($item['icon_class']); ?>"></i>
                            <div class="info-item-content">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No contact information to display.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>