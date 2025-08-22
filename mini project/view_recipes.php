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
    
    // Delete the recipe directly (no need to delete likes anymore)
    $stmt = $conn->prepare("DELETE FROM recipes WHERE id = ?");
    $stmt->bind_param("i", $recipe_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Recipe deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting recipe']);
    }
}

// Get all recipes for admin management
function getRecipesForAdmin($conn, $filter = 'all') {
    $sql = "SELECT r.*, u.fullname as author_name, u.email as author_email
            FROM recipes r 
            JOIN users u ON r.user_id = u.id";
    
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Georgia', serif;
            background: linear-gradient(135deg, #f5f3f0 0%, #e8e5e0 100%);
            color: #2c1810;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #8b4513 0%, #a0522d 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .admin-nav {
            background: white;
            margin: 1rem 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .filter-tabs {
            display: flex;
            padding: 1rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            background: white;
            border: 2px solid #d4af37;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #d4af37;
            color: white;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #8b4513;
        }

        .stat-label {
            color: #666;
        }

        .recipe-list {
            margin: 2rem 0;
        }

        .recipe-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
        }

        .recipe-image {
            width: 200px;
            min-width: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .recipe-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .recipe-content {
            padding: 1.5rem;
            flex-grow: 1;
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
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
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
            max-width: 600px;
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

        @media (max-width: 768px) {
            .recipe-card {
                flex-direction: column;
            }
            
            .recipe-image {
                width: 100%;
                height: 200px;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>Westley's Resto Café - Recipe Management</h1>
            </div>
        </div>
    </div>

    <div class="container">
        <div id="alert-container"></div>

        <div class="admin-nav">
            <div class="filter-tabs">
                <button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" onclick="filterRecipes('all')">All Recipes</button>
                <button class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>" onclick="filterRecipes('pending')">Pending Approval</button>
                <button class="filter-btn <?= $filter === 'approved' ? 'active' : '' ?>" onclick="filterRecipes('approved')">Approved</button>
                <button class="filter-btn <?= $filter === 'featured' ? 'active' : '' ?>" onclick="filterRecipes('featured')">Featured</button>
            </div>
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
                <div style="text-align: center; padding: 2rem; background: white; border-radius: 10px;">
                    <h3>No recipes found</h3>
                    <p>No recipes match the selected filter.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <div class="recipe-card" id="recipe-<?= $recipe['id'] ?>">
                        <div class="recipe-image">
                            <?php if ($recipe['photo_url']): ?>
                                <img src="<?= $recipe['photo_url'] ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
                            <?php else: ?>
                                <span>📷 No Photo</span>
                            <?php endif; ?>
                        </div>
                        <div class="recipe-content">
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
                                    <button class="action-btn btn-approve" onclick="approveRecipe(<?= $recipe['id'] ?>)">Approve</button>
                                <?php endif; ?>
                                
                                <?php if ($recipe['status'] === 'approved'): ?>
                                    <?php if ($recipe['featured']): ?>
                                        <button class="action-btn btn-unfeature" onclick="unfeatureRecipe(<?= $recipe['id'] ?>)">Remove Feature</button>
                                    <?php else: ?>
                                        <button class="action-btn btn-feature" onclick="featureRecipe(<?= $recipe['id'] ?>)">Feature Recipe</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <button class="action-btn btn-delete" onclick="deleteRecipe(<?= $recipe['id'] ?>)">Delete</button>
                                <button class="action-btn" onclick="viewRecipe(<?= $recipe['id'] ?>)">View Details</button>
                            </div>
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
                        'Content-Type': 'application/x-www-form-urlencoded',
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

        // View recipe details
        async function viewRecipe(recipeId) {
            try {
                // In a real implementation, you would fetch the full recipe details
                // For now, we'll just show the basic info we already have
                const recipeCard = document.getElementById(`recipe-${recipeId}`);
                const title = recipeCard.querySelector('.recipe-title').textContent;
                const author = recipeCard.querySelector('.recipe-meta').textContent;
                const description = recipeCard.querySelector('.recipe-description').textContent;
                
                const modal = document.getElementById('recipeModal');
                const modalContent = document.getElementById('modalContent');
                
                modalContent.innerHTML = `
                    <h2>${title}</h2>
                    <p><strong>${author}</strong></p>
                    <p>${description}</p>
                    <h3>Ingredients and instructions would appear here in a full implementation.</h3>
                `;
                
                modal.style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error loading recipe details', 'error');
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