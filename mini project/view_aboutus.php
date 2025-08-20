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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Why Us section handling
    if (isset($_POST['why_us_action'])) {
        handleWhyUsAction($conn);
    }
    
    // About video/content handling
    if (isset($_POST['about_action'])) {
        handleAboutAction($conn);
    }
    
    // Chefs handling
    if (isset($_POST['chefs_action'])) {
        handleChefsAction($conn);
    }
}

function handleWhyUsAction($conn) {
    $action = $_POST['why_us_action'];
    
    if ($action === 'add') {
        $number = $_POST['number'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO why_us_items (number, title, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $number, $title, $description);
        $stmt->execute();
        $stmt->close();
    } 
    elseif ($action === 'update') {
        $id = $_POST['id'];
        $number = $_POST['number'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("UPDATE why_us_items SET number=?, title=?, description=? WHERE id=?");
        $stmt->bind_param("sssi", $number, $title, $description, $id);
        $stmt->execute();
        $stmt->close();
    } 
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM why_us_items WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

function handleAboutAction($conn) {
    $action = $_POST['about_action'];
    
    if ($action === 'update') {
        $id = 1; // Assuming we only have one about section
        $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $content = $_POST['content'];
        
        // Handle video upload
        $video_path = $_POST['current_video'];
        if (!empty($_FILES['video']['name'])) {
            $target_dir = "assets/videos/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $video_path = $target_dir . basename($_FILES["video"]["name"]);
            move_uploaded_file($_FILES["video"]["tmp_name"], $video_path);
        }
        
        // Handle image upload
        $fallback_image = $_POST['current_image'];
        if (!empty($_FILES['fallback_image']['name'])) {
            $target_dir = "assets/img/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $fallback_image = $target_dir . basename($_FILES["fallback_image"]["name"]);
            move_uploaded_file($_FILES["fallback_image"]["tmp_name"], $fallback_image);
        }
        
        $stmt = $conn->prepare("UPDATE about_content SET title=?, subtitle=?, content=?, video_path=?, fallback_image=? WHERE id=?");
        $stmt->bind_param("sssssi", $title, $subtitle, $content, $video_path, $fallback_image, $id);
        $stmt->execute();
        $stmt->close();
    }
}

function handleChefsAction($conn) {
    $action = $_POST['chefs_action'];
    
    if ($action === 'add') {
        $name = $_POST['name'];
        $position = $_POST['position'];
        $bio = $_POST['bio'];
        
        // Handle image upload
        $image_path = "";
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "assets/img/chefs/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_path = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
        }
        
        $stmt = $conn->prepare("INSERT INTO chefs (name, position, bio, image_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $position, $bio, $image_path);
        $stmt->execute();
        $stmt->close();
    } 
    elseif ($action === 'update') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $position = $_POST['position'];
        $bio = $_POST['bio'];
        
        // Handle image upload if new image is provided
        $image_path = $_POST['current_image'];
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "assets/img/chefs/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_path = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
        }
        
        $stmt = $conn->prepare("UPDATE chefs SET name=?, position=?, bio=?, image_path=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $position, $bio, $image_path, $id);
        $stmt->execute();
        $stmt->close();
    } 
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM chefs WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch current data
$why_us_items = $conn->query("SELECT * FROM why_us_items WHERE is_active = 1 ORDER BY number");
$about_content = $conn->query("SELECT * FROM about_content WHERE id = 1");
$chefs = $conn->query("SELECT * FROM chefs WHERE is_active = 1 ORDER BY name");

// Initialize default about content if not exists
if ($about_content->num_rows === 0) {
    $conn->query("INSERT INTO about_content (title, subtitle, content, video_path, fallback_image) 
                 VALUES ('Our Culinary Journey', 'About Our Restaurant', 
                 'Founded in 2020, our restaurant has been serving exquisite dishes that blend traditional flavors with contemporary techniques.', 
                 'cook1.mp4', 'assets/img/about.jpg')");
    $about_content = $conn->query("SELECT * FROM about_content WHERE id = 1");
}
$about = $about_content->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage About Us - Westley's Resto Cafe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --primary: #2d3748;
      --light: #f7fafc;
      --lighter: #fff;
      --border: #e2e8f0;
      --radius: .375rem;
      --shadow: 0 1px 3px rgba(0,0,0,.08);
      --success: #38a169;
      --danger: #e53e3e;
      --warning: #dd6b20;
      --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      --accent-color: #cda45e;
      --contrast-color: #0c0b09;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--light);
      color: var(--primary);
      padding: 2rem;
      line-height: 1.6;
    }

    .admin-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Playfair Display', sans-serif;
    }

    .header {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 1.5rem;
      position: relative;
    }

    .header h1 {
      font-size: 1.8rem;
      margin-bottom: 1rem;
      animation: fadeIn 0.5s ease-out;
      text-align: center;
    }
    
    /* Tabs */
    .tabs {
      display: flex;
      margin-bottom: 20px;
      border-bottom: 1px solid var(--border);
    }
    
    .tab {
      padding: 10px 20px;
      cursor: pointer;
      border-bottom: 3px solid transparent;
      transition: var(--transition);
      background: transparent;
      color: var(--primary);
      border: none;
    }
    
    .tab:hover {
      background: rgba(45, 55, 72, 0.05);
    }
    
    .tab.active {
      border-bottom-color: var(--primary);
      font-weight: 500;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
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
    
    input[type="text"], textarea {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: 'Inter', sans-serif;
      transition: var(--transition);
    }
    
    input[type="text"]:focus, textarea:focus {
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
    
    .preview-image {
      max-width: 200px;
      max-height: 200px;
      border-radius: var(--radius);
    }
    
    /* Edit/Delete Buttons (preserved from original) */
    .item-actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 1rem;
    }
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #5c636a;
    }
    
    .btn-danger {
      background-color: #dc3545;
      color: white;
    }
    
    .btn-danger:hover {
      background-color: #bb2d3b;
    }
    
    /* BIG VIEW for Why Us Items */
    .big-view-container {
      margin-top: 2rem;
    }
    
    .big-view-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    
    .big-view-title {
      font-size: 1.5rem;
      color: var(--primary);
      margin: 0;
    }
    
    .why-us-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
    }
    
    .why-us-card {
      background: var(--lighter);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 1.5rem;
      transition: var(--transition);
      border-top: 4px solid var(--accent-color);
      position: relative;
      overflow: hidden;
    }
    
    .why-us-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .why-us-number {
      position: absolute;
      top: 0;
      right: 0;
      background: var(--accent-color);
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-bottom-left-radius: var(--radius);
    }
    
    .why-us-card h3 {
      margin-top: 0;
      margin-bottom: 0.75rem;
      color: var(--primary);
      font-size: 1.25rem;
      padding-right: 40px;
    }
    
    .why-us-card p {
      color: #4a5568;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }
    
    .card-actions {
      display: flex;
      gap: 0.5rem;
      justify-content: flex-end;
      border-top: 1px solid var(--border);
      padding-top: 1rem;
      margin-top: auto;
    }
    
    .edit-form {
      background: rgba(247, 250, 252, 0.8);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-top: 1rem;
      border: 1px solid var(--border);
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px) }
      to { opacity: 1; transform: translateY(0) }
    }
    
    @media (max-width: 768px) {
      body { padding: 1rem }
      .item-card { flex-direction: column }
      .why-us-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <div class="header">
      <h1>Manage About Us Page</h1>
    </div>
    
    <div class="tabs">
      <button class="tab active" onclick="openTab('why-us')">Why Us Section</button>
      <button class="tab" onclick="openTab('about')">About Content</button>
      <button class="tab" onclick="openTab('chefs')">Chefs Section</button>
    </div>
    
    <!-- Why Us Section Management -->
    <div id="why-us" class="tab-content active">
      <div class="card">
        <h2>Add New Why Us Item</h2>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="why_us_action" value="add">
          <div class="form-group">
            <label for="number">Number:</label>
            <input type="text" id="number" name="number" required>
          </div>
          <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>
          </div>
          <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>
          </div>
          <button type="submit" class="btn" style="background: var(--primary); color: white;">Add Item</button>
        </form>
      </div>
      
      <!-- Big View for Why Us Items -->
      <div class="big-view-container">
        <div class="big-view-header">
          <h2 class="big-view-title">Current Why Us Items</h2>
          <span class="item-count"><?php echo $why_us_items->num_rows; ?> items</span>
        </div>
        
        <div class="why-us-grid">
          <?php 
          // Reset pointer and loop through items again
          $why_us_items->data_seek(0);
          while($item = $why_us_items->fetch_assoc()): 
          ?>
          <div class="why-us-card">
            <div class="why-us-number"><?php echo htmlspecialchars($item['number']); ?></div>
            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
            <p><?php echo htmlspecialchars($item['description']); ?></p>
            
            <div class="card-actions">
              <button class="btn btn-secondary" onclick="toggleEditForm(<?php echo $item['id']; ?>)">
                <i class="fas fa-edit"></i> Edit
              </button>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="why_us_action" value="delete">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </form>
            </div>
            
            <div id="edit-form-<?php echo $item['id']; ?>" class="edit-form" style="display: none;">
              <h4>Edit Item</h4>
              <form method="POST">
                <input type="hidden" name="why_us_action" value="update">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <div class="form-group">
                  <label>Number:</label>
                  <input type="text" name="number" value="<?php echo htmlspecialchars($item['number']); ?>" required>
                </div>
                <div class="form-group">
                  <label>Title:</label>
                  <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                </div>
                <div class="form-group">
                  <label>Description:</label>
                  <textarea name="description" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>
                <div class="form-group">
                  <button type="submit" class="btn btn-secondary">Update</button>
                  <button type="button" class="btn" onclick="toggleEditForm(<?php echo $item['id']; ?>)" style="background: var(--border);">Cancel</button>
                </div>
              </form>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
    
    <!-- About Content Management -->
    <div id="about" class="tab-content">
      <div class="card">
        <h2>Update About Content</h2>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="about_action" value="update">
          <div class="form-group">
            <label for="about_title">Title:</label>
            <input type="text" id="about_title" name="title" value="<?php echo htmlspecialchars($about['title']); ?>" required>
          </div>
          <div class="form-group">
            <label for="subtitle">Subtitle:</label>
            <input type="text" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($about['subtitle']); ?>" required>
          </div>
          <div class="form-group">
            <label for="about_content">Content:</label>
            <textarea id="about_content" name="content" required><?php echo htmlspecialchars($about['content']); ?></textarea>
          </div>
          <div class="form-group">
            <label for="video">Video (Leave empty to keep current):</label>
            <input type="file" id="video" name="video" accept="video/*">
            <input type="hidden" name="current_video" value="<?php echo htmlspecialchars($about['video_path']); ?>">
            <?php if ($about['video_path']): ?>
            <p>Current: <?php echo $about['video_path']; ?></p>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label for="fallback_image">Fallback Image (Leave empty to keep current):</label>
            <input type="file" id="fallback_image" name="fallback_image" accept="image/*">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($about['fallback_image']); ?>">
            <?php if ($about['fallback_image']): ?>
            <img src="<?php echo $about['fallback_image']; ?>" class="preview-image" alt="Current image">
            <?php endif; ?>
          </div>
          <button type="submit" class="btn" style="background: var(--primary); color: white;">Update Content</button>
        </form>
      </div>
    </div>
    
    <!-- Chefs Section Management -->
    <div id="chefs" class="tab-content">
      <div class="card">
        <h2>Add New Chef</h2>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="chefs_action" value="add">
          <div class="form-group">
            <label for="chef_name">Name:</label>
            <input type="text" id="chef_name" name="name" required>
          </div>
          <div class="form-group">
            <label for="position">Position:</label>
            <input type="text" id="position" name="position" required>
          </div>
          <div class="form-group">
            <label for="bio">Bio:</label>
            <textarea id="bio" name="bio" required></textarea>
          </div>
          <div class="form-group">
            <label for="chef_image">Image:</label>
            <input type="file" id="chef_image" name="image" accept="image/*" required>
          </div>
          <button type="submit" class="btn" style="background: var(--primary); color: white;">Add Chef</button>
        </form>
      </div>
      
      <div class="card">
        <h2>Current Chefs</h2>
        <div class="item-list">
          <?php while($chef = $chefs->fetch_assoc()): ?>
          <div class="item-card">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="chefs_action" value="update">
              <input type="hidden" name="id" value="<?php echo $chef['id']; ?>">
              <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($chef['name']); ?>" required>
              </div>
              <div class="form-group">
                <label>Position:</label>
                <input type="text" name="position" value="<?php echo htmlspecialchars($chef['position']); ?>" required>
              </div>
              <div class="form-group">
                <label>Bio:</label>
                <textarea name="bio" required><?php echo htmlspecialchars($chef['bio']); ?></textarea>
              </div>
              <div class="form-group">
                <label>Image (Leave empty to keep current):</label>
                <input type="file" name="image" accept="image/*">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($chef['image_path']); ?>">
                <?php if ($chef['image_path']): ?>
                <img src="<?php echo $chef['image_path']; ?>" class="preview-image" alt="Chef image">
                <?php endif; ?>
              </div>
              <button type="submit" class="btn btn-secondary">Update</button>
            </form>
            <form method="POST" style="margin-top: 10px;">
              <input type="hidden" name="chefs_action" value="delete">
              <input type="hidden" name="id" value="<?php echo $chef['id']; ?>">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    function openTab(tabName) {
      // Hide all tab contents
      var tabContents = document.getElementsByClassName("tab-content");
      for (var i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove("active");
      }
      
      // Deactivate all tabs
      var tabs = document.getElementsByClassName("tab");
      for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("active");
      }
      
      // Activate the selected tab
      document.getElementById(tabName).classList.add("active");
      
      // Find the button that was clicked and activate it
      event.currentTarget.classList.add("active");
    }
    
    function toggleEditForm(itemId) {
      const form = document.getElementById('edit-form-' + itemId);
      if (form.style.display === 'none') {
        form.style.display = 'block';
      } else {
        form.style.display = 'none';
      }
    }
  </script>
</body>
</html>