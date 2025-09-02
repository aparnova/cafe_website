<?php
session_start();

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

// Set charset to handle special characters
$conn->set_charset("utf8");

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'approve_recipe':
            approveRecipe($conn);
            break;
        case 'feature_recipe':
            toggleFeatureRecipe($conn, true);
            break;
        case 'unfeature_recipe':
            toggleFeatureRecipe($conn, false);
            break;
        case 'delete_recipe':
            deleteRecipe($conn);
            break;
        case 'get_recipe_details':
            getRecipeDetails($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Admin functions
function approveRecipe($conn) {
    $recipe_id = intval($_POST['recipe_id']);
    
    $stmt = $conn->prepare("UPDATE recipes SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $recipe_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Recipe approved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error approving recipe']);
    }
}

function toggleFeatureRecipe($conn, $feature) {
    $recipe_id = intval($_POST['recipe_id']);
    $feature_value = $feature ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE recipes SET featured = ? WHERE id = ?");
    $stmt->bind_param("ii", $feature_value, $recipe_id);
    
    if ($stmt->execute()) {
        $action = $feature ? 'featured' : 'unfeatured';
        echo json_encode(['success' => true, 'message' => "Recipe $action"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating recipe']);
    }
}

function deleteRecipe($conn) {
    $recipe_id = intval($_POST['recipe_id']);
    
    // Start transaction to ensure all related data is deleted
    $conn->begin_transaction();
    
    try {
        // Delete ingredients
        $stmt1 = $conn->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
        $stmt1->bind_param("i", $recipe_id);
        $stmt1->execute();
        
        // Delete instructions
        $stmt2 = $conn->prepare("DELETE FROM recipe_instructions WHERE recipe_id = ?");
        $stmt2->bind_param("i", $recipe_id);
        $stmt2->execute();
        
        // Delete the recipe
        $stmt3 = $conn->prepare("DELETE FROM recipes WHERE id = ?");
        $stmt3->bind_param("i", $recipe_id);
        $stmt3->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Recipe deleted']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting recipe: ' . $e->getMessage()]);
    }
}

function getRecipeDetails($conn) {
    $recipe_id = intval($_POST['recipe_id']);
    
    // Get recipe details with author info
    $sql = "SELECT r.*, a.name as author_name, a.email as author_email
            FROM recipes r 
            JOIN authors a ON r.author_id = a.id
            WHERE r.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $recipe = $result->fetch_assoc();
        
        // Get ingredients
        $ingredients_sql = "SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id";
        $ingredients_stmt = $conn->prepare($ingredients_sql);
        $ingredients_stmt->bind_param("i", $recipe_id);
        $ingredients_stmt->execute();
        $ingredients_result = $ingredients_stmt->get_result();
        
        $ingredients = [];
        while ($row = $ingredients_result->fetch_assoc()) {
            $ingredients[] = $row['ingredient'];
        }
        
        // Get instructions
        $instructions_sql = "SELECT step_number, instruction FROM recipe_instructions WHERE recipe_id = ? ORDER BY step_number";
        $instructions_stmt = $conn->prepare($instructions_sql);
        $instructions_stmt->bind_param("i", $recipe_id);
        $instructions_stmt->execute();
        $instructions_result = $instructions_stmt->get_result();
        
        $instructions = [];
        while ($row = $instructions_result->fetch_assoc()) {
            $instructions[] = $row;
        }
        
        echo json_encode([
            'success' => true, 
            'recipe' => $recipe,
            'ingredients' => $ingredients,
            'instructions' => $instructions
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Recipe not found']);
    }
}

// Get all recipes for admin management
function getRecipesForAdmin($conn, $filter = 'all') {
    $sql = "SELECT r.*, a.name as author_name, a.email as author_email
            FROM recipes r 
            JOIN authors a ON r.author_id = a.id";
    
    switch ($filter) {
        case 'pending':
            $sql .= " WHERE r.status = 'pending'";
            break;
        case 'approved':
            $sql .= " WHERE r.status = 'approved'";
            break;
        case 'featured':
            $sql .= " WHERE r.featured = 1";
            break;
        default:
            // No filter - get all
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    $result = $conn->query($sql);
    $recipes = [];
    
    while ($row = $result->fetch_assoc()) {
        $recipes[] = $row;
    }
    
    return $recipes;
}

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';
$recipes = getRecipesForAdmin($conn, $filter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Recipe Management - Westley's Resto Café</title>
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
      margin: 0;
      padding: 0;
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
    
    /* Stats */
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin: 1.5rem 0;
    }
    
    .stat-card {
      background: var(--lighter);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      transform: translateY(0);
      transition: var(--transition);
      padding: 1.5rem;
      text-align: center;
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #8b4513;
    }
    
    .stat-label {
      color: #666;
    }
    
    /* Recipe List */
    .recipe-list {
      margin: 2rem 0;
    }
    
    .recipe-card {
      background: var(--lighter);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      transform: translateY(0);
      transition: var(--transition);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      border-top: 4px solid var(--accent-color);
    }
    
    .recipe-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    /* Recipe image container - shows full image */
    .recipe-image-container {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      border-radius: var(--radius);
      overflow: hidden;
      background: #f0f0f0;
      max-height: none;
    }
    
    /* Recipe image - shows full image without cropping */
    .recipe-image {
      max-width: 100%;
      height: auto;
      object-fit: contain;
      display: block;
    }
    
    .recipe-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }
    
    .recipe-title {
      font-size: 1.3rem;
      font-weight: bold;
      color: #8b4513;
      margin-right: 10px;
    }
    
    .recipe-status {
      padding: 4px 8px;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: bold;
      white-space: nowrap;
    }
    
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    
    .status-approved {
      background: #d4edda;
      color: #155724;
    }
    
    .recipe-meta {
      color: #666;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
    
    .recipe-description {
      color: #444;
      margin-bottom: 1rem;
      line-height: 1.5;
    }
    
    .recipe-actions {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .action-btn {
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
    
    .action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-approve {
      background: #28a745;
      color: white;
    }
    
    .btn-approve:hover {
      background: #218838;
    }
    
    .btn-feature {
      background: #ffc107;
      color: black;
    }
    
    .btn-feature:hover {
      background: #e0a800;
    }
    
    .btn-unfeature {
      background: #6c757d;
      color: white;
    }
    
    .btn-unfeature:hover {
      background: #5a6268;
    }
    
    /* Preserved delete button styles from original view_recipes */
    .btn-delete {
      background: #dc3545;
      color: white;
    }
    
    .btn-delete:hover {
      background: #c82333;
    }
    
    .alert {
      padding: 1rem;
      margin: 1rem 0;
      border-radius: 8px;
      font-weight: bold;
    }
    
    .alert.success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      backdrop-filter: blur(5px);
    }
    
    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 15px;
      width: 90%;
      max-width: 800px;
      max-height: 80vh;
      overflow-y: auto;
      position: relative;
    }
    
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      position: absolute;
      right: 20px;
      top: 15px;
    }
    
    .close:hover {
      color: #000;
    }
    
    /* Modal content styles */
    .modal-recipe-image {
      width: 100%;
      max-height: 300px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    
    .modal-section {
      margin-bottom: 1.5rem;
    }
    
    .modal-section h3 {
      color: #8b4513;
      margin-bottom: 0.5rem;
      border-bottom: 2px solid var(--accent-color);
      padding-bottom: 0.25rem;
    }
    
    .ingredients-list {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    
    .ingredients-list ul {
      list-style-type: disc;
      padding-left: 1.5rem;
    }
    
    .ingredients-list li {
      padding: 0.25rem 0;
      border-bottom: 1px solid #e9ecef;
    }
    
    .ingredients-list li:last-child {
      border-bottom: none;
    }
    
    .instructions-list {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
    }
    
    .instruction-step {
      margin-bottom: 0.75rem;
      padding-left: 1.5rem;
      position: relative;
    }
    
    .instruction-step:before {
      content: counter(step);
      counter-increment: step;
      position: absolute;
      left: 0;
      background: var(--accent-color);
      color: white;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
      font-weight: bold;
    }
    
    .instructions-list {
      counter-reset: step;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px) }
      to { opacity: 1; transform: translateY(0) }
    }
    
    @media (max-width: 768px) {
      body { 
        padding: 1rem; 
      }
      
      .recipe-card {
        flex-direction: column;
      }
      
      .recipe-actions {
        flex-direction: column;
      }
      
      .action-btn {
        width: 100%;
      }
      
      .recipe-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .recipe-status {
        margin-top: 0.5rem;
      }
      
      .tabs {
        flex-direction: column;
      }
      
      .tab {
        width: 100%;
        text-align: center;
      }
      
      .modal-content {
        margin: 10% auto;
        width: 95%;
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <div class="header">
      <h1>Westley's Resto Cafe - Recipe Management</h1>
    </div>

    <div id="alert-container"></div>

    <div class="tabs">
      <button class="tab <?= $filter === 'all' ? 'active' : '' ?>" onclick="filterRecipes('all')">All Recipes</button>
      <button class="tab <?= $filter === 'pending' ? 'active' : '' ?>" onclick="filterRecipes('pending')">Pending Approval</button>
      <button class="tab <?= $filter === 'approved' ? 'active' : '' ?>" onclick="filterRecipes('approved')">Approved</button>
      <button class="tab <?= $filter === 'featured' ? 'active' : '' ?>" onclick="filterRecipes('featured')">Featured</button>
    </div>

    <?php
    // Get stats for dashboard
    $total_recipes = $conn->query("SELECT COUNT(*) as count FROM recipes")->fetch_assoc()['count'];
    $pending_recipes = $conn->query("SELECT COUNT(*) as count FROM recipes WHERE status = 'pending'")->fetch_assoc()['count'];
    $featured_recipes = $conn->query("SELECT COUNT(*) as count FROM recipes WHERE featured = 1")->fetch_assoc()['count'];
    ?>
    
    <div class="stats">
      <div class="stat-card">
        <div class="stat-number"><?= $total_recipes ?></div>
        <div class="stat-label">Total Recipes</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $pending_recipes ?></div>
        <div class="stat-label">Pending Approval</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $featured_recipes ?></div>
        <div class="stat-label">Featured Recipes</div>
      </div>
    </div>

    <div class="recipe-list">
      <?php if (count($recipes) === 0): ?>
        <div class="card" style="text-align: center;">
          <h3>No recipes found</h3>
          <p>No recipes match the selected filter.</p>
        </div>
      <?php else: ?>
        <?php foreach ($recipes as $recipe): ?>
          <div class="recipe-card" id="recipe-<?= $recipe['id'] ?>">
            <div class="recipe-image-container">
              <?php if ($recipe['photo_url']): ?>
                <img src="<?= $recipe['photo_url'] ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="recipe-image">
              <?php else: ?>
                <span>📷 No Photo</span>
              <?php endif; ?>
            </div>
            
            <div class="recipe-header">
              <div class="recipe-title"><?= htmlspecialchars($recipe['title']) ?></div>
              <div class="recipe-status status-<?= $recipe['status'] ?>">
                <?= ucfirst($recipe['status']) ?>
                <?= $recipe['featured'] ? ' • Featured' : '' ?>
              </div>
            </div>
            
            <div class="recipe-meta">
              By: <?= htmlspecialchars($recipe['author_name']) ?> (<?= htmlspecialchars($recipe['author_email']) ?>)<br>
              Submitted: <?= date('M j, Y', strtotime($recipe['created_at'])) ?>
            </div>
            
            <div class="recipe-description">
              <?= nl2br(htmlspecialchars($recipe['description'] ?? 'No description')) ?>
            </div>
            
            <div class="recipe-actions">
              <?php if ($recipe['status'] === 'pending'): ?>
                <button class="action-btn btn-approve" onclick="approveRecipe(<?= $recipe['id'] ?>)">
                  <i class="fas fa-check"></i> Approve
                </button>
              <?php endif; ?>
              
              <?php if ($recipe['status'] === 'approved'): ?>
                <?php if ($recipe['featured']): ?>
                  <button class="action-btn btn-unfeature" onclick="unfeatureRecipe(<?= $recipe['id'] ?>)">
                    <i class="fas fa-star"></i> Remove Feature
                  </button>
                <?php else: ?>
                  <button class="action-btn btn-feature" onclick="featureRecipe(<?= $recipe['id'] ?>)">
                    <i class="fas fa-star"></i> Feature Recipe
                  </button>
                <?php endif; ?>
              <?php endif; ?>
              
              <button class="action-btn btn-delete" onclick="deleteRecipe(<?= $recipe['id'] ?>)">
                <i class="fas fa-trash"></i> Delete
              </button>
              
              <button class="action-btn" onclick="viewRecipe(<?= $recipe['id'] ?>)" style="background: var(--primary); color: white;">
                <i class="fas fa-eye"></i> View Details
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recipe Detail Modal -->
  <div id="recipeModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <div id="modalContent">
        <!-- Recipe details will be loaded here -->
      </div>
    </div>
  </div>

  <script>
    // Show alert message
    function showAlert(message, type) {
      const container = document.getElementById('alert-container');
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert ${type}`;
      alertDiv.textContent = message;
      
      container.appendChild(alertDiv);
      
      // Auto-remove after 5 seconds
      setTimeout(() => {
        if (container.contains(alertDiv)) {
          container.removeChild(alertDiv);
        }
      }, 5000);
      
      // Scroll to top to show alert
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Filter recipes
    function filterRecipes(filter) {
      window.location.href = `?filter=${filter}`;
    }

    // Approve recipe
    async function approveRecipe(recipeId) {
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=approve_recipe&recipe_id=${recipeId}`
        });
        
        const data = await response.json();
        if (data.success) {
          showAlert(data.message, 'success');
          // Reload after a short delay
          setTimeout(() => location.reload(), 1000);
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showAlert('Error approving recipe', 'error');
      }
    }

    // Feature recipe
    async function featureRecipe(recipeId) {
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=feature_recipe&recipe_id=${recipeId}`
        });
        
        const data = await response.json();
        if (data.success) {
          showAlert(data.message, 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showAlert('Error featuring recipe', 'error');
      }
    }

    // Unfeature recipe
    async function unfeatureRecipe(recipeId) {
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application-x-www-form-urlencoded',
          },
          body: `action=unfeature_recipe&recipe_id=${recipeId}`
        });
        
        const data = await response.json();
        if (data.success) {
          showAlert(data.message, 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showAlert('Error unfeaturing recipe', 'error');
      }
    }

    // Delete recipe
    async function deleteRecipe(recipeId) {
      if (!confirm('Are you sure you want to delete this recipe? This action cannot be undone.')) {
        return;
      }
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=delete_recipe&recipe_id=${recipeId}`
        });
        
        const data = await response.json();
        if (data.success) {
          showAlert(data.message, 'success');
          // Remove the recipe card from view
          document.getElementById(`recipe-${recipeId}`).remove();
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showAlert('Error deleting recipe', 'error');
      }
    }

    // View recipe details with ingredients and instructions
    async function viewRecipe(recipeId) {
      try {
        // Fetch the full recipe details
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=get_recipe_details&recipe_id=${recipeId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
          const recipe = data.recipe;
          const ingredients = data.ingredients;
          const instructions = data.instructions;
          
          const modal = document.getElementById('recipeModal');
          const modalContent = document.getElementById('modalContent');
          
          // Format ingredients list as bullet points
          let ingredientsHtml = '';
          if (ingredients.length > 0) {
            ingredientsHtml = '<ul style="list-style-type: disc; padding-left: 1.5rem;">';
            ingredients.forEach(ingredient => {
              ingredientsHtml += `<li style="margin-bottom: 0.5rem;">${ingredient}</li>`;
            });
            ingredientsHtml += '</ul>';
          } else {
            ingredientsHtml = '<div>No ingredients listed</div>';
          }
          
          // Format instructions
          let instructionsHtml = '';
          if (instructions.length > 0) {
            instructionsHtml = instructions.map((instruction, index) => 
              `<div class="instruction-step">${instruction.instruction}</div>`
            ).join('');
          } else {
            instructionsHtml = '<div>No instructions provided</div>';
          }
          
          modalContent.innerHTML = `
            <h2 style="color: #8b4513; margin-bottom: 1rem; border-bottom: 2px solid var(--accent-color); padding-bottom: 0.5rem;">${recipe.title}</h2>
            
            <div style="color: #666; font-style: italic; margin-bottom: 1.5rem;">
              By: ${recipe.author_name} (${recipe.author_email})<br>
              Submitted: ${new Date(recipe.created_at).toLocaleDateString()}
            </div>
            
            ${recipe.photo_url ? `
              <img src="${recipe.photo_url}" alt="${recipe.title}" class="modal-recipe-image">
            ` : ''}
            
            ${recipe.description ? `
              <div class="modal-section">
                <h3>Description</h3>
                <p>${recipe.description}</p>
              </div>
            ` : ''}
            
            <div class="modal-section">
              <h3>Ingredients</h3>
              <div class="ingredients-list">
                ${ingredientsHtml}
              </div>
            </div>
            
            <div class="modal-section">
              <h3>Instructions</h3>
              <div class="instructions-list">
                ${instructionsHtml}
              </div>
            </div>
          `;
          
          modal.style.display = 'block';
        } else {
          showAlert('Error loading recipe details: ' + data.message, 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showAlert('Error loading recipe details. Please try again.', 'error');
      }
    }

    // Modal functionality
    const modal = document.getElementById('recipeModal');
    const closeBtn = document.querySelector('.close');
    
    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
      if (e.target === modal) modal.style.display = 'none';
    });
  </script>
</body>
</html>