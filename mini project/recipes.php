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

// Set charset to handle special characters
$conn->set_charset("utf8");

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'submit_recipe':
            submitRecipe($conn);
            break;
        case 'get_recipes':
            getRecipes($conn);
            break;
        case 'get_featured':
            getFeaturedRecipes($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Functions
function submitRecipe($conn) {
    try {
        $conn->begin_transaction();
        
        $fullname = trim($_POST['authorName'] ?? '');
        $email = trim($_POST['authorEmail'] ?? '');
        $title = trim($_POST['recipeTitle'] ?? '');
        $description = trim($_POST['recipeDescription'] ?? '');
        $ingredients = trim($_POST['ingredients'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        
        // Validation
        if (empty($fullname) || empty($email) || empty($title) || empty($ingredients) || empty($instructions)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
            return;
        }
        
        // Handle photo upload
        $photo_url = null;
        if (isset($_FILES['recipePhoto']) && $_FILES['recipePhoto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['recipePhoto']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $photo_name = 'recipe_' . time() . '_' . uniqid() . '.' . $file_extension;
                $photo_path = $upload_dir . $photo_name;
                
                if (move_uploaded_file($_FILES['recipePhoto']['tmp_name'], $photo_path)) {
                    $photo_url = $photo_path;
                }
            }
        }
        
        // Check if author exists, if not create new author
        $author_stmt = $conn->prepare("SELECT id FROM authors WHERE email = ?");
        $author_stmt->bind_param("s", $email);
        $author_stmt->execute();
        $author_result = $author_stmt->get_result();
        
        if ($author_result->num_rows > 0) {
            $author_id = $author_result->fetch_assoc()['id'];
        } else {
            // Create new author
            $insert_author_stmt = $conn->prepare("INSERT INTO authors (name, email) VALUES (?, ?)");
            $insert_author_stmt->bind_param("ss", $fullname, $email);
            $insert_author_stmt->execute();
            $author_id = $conn->insert_id;
        }
        
        // Insert recipe
        $recipe_stmt = $conn->prepare("INSERT INTO recipes (author_id, title, description, photo_url, status) VALUES (?, ?, ?, ?, 'pending')");
        $recipe_stmt->bind_param("isss", $author_id, $title, $description, $photo_url);
        
        if ($recipe_stmt->execute()) {
            $recipe_id = $conn->insert_id;
            
            // Insert ingredients
            $ingredients_array = array_map('trim', explode(',', $ingredients));
            $ingredient_stmt = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient) VALUES (?, ?)");
            
            foreach ($ingredients_array as $ingredient) {
                if (!empty($ingredient)) {
                    $ingredient_stmt->bind_param("is", $recipe_id, $ingredient);
                    $ingredient_stmt->execute();
                }
            }
            
            // Insert instructions
            $instructions_array = preg_split('/\d+\.|\n/', $instructions);
            $instruction_stmt = $conn->prepare("INSERT INTO recipe_instructions (recipe_id, step_number, instruction) VALUES (?, ?, ?)");
            
            $step_number = 1;
            foreach ($instructions_array as $instruction) {
                $instruction = trim($instruction);
                if (!empty($instruction)) {
                    $instruction_stmt->bind_param("iis", $recipe_id, $step_number, $instruction);
                    $instruction_stmt->execute();
                    $step_number++;
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Recipe submitted successfully! It will be reviewed before publishing.']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error submitting recipe']);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}

function getRecipes($conn) {
    try {
        $filter = $_POST['filter'] ?? $_GET['filter'] ?? 'all';
        $search = $_POST['search'] ?? $_GET['search'] ?? '';
        
        // Query with JOIN to get author information
        $sql = "SELECT r.*, a.name as author_name, a.email as author_email 
                FROM recipes r 
                JOIN authors a ON r.author_id = a.id 
                WHERE r.status = 'approved'";
        
        $params = [];
        $types = "";
        
        if (!empty($search)) {
            $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
            $search_param = "%$search%";
            $params = [$search_param, $search_param];
            $types = "ss";
        }
        
        switch ($filter) {
            case 'recent':
                $sql .= " ORDER BY r.created_at DESC";
                break;
            case 'featured':
                $sql .= " AND r.featured = 1 ORDER BY r.created_at DESC";
                break;
            default:
                $sql .= " ORDER BY r.created_at DESC";
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $recipes = [];
        while ($row = $result->fetch_assoc()) {
            // Get ingredients for this recipe
            $ingredients_stmt = $conn->prepare("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ?");
            $ingredients_stmt->bind_param("i", $row['id']);
            $ingredients_stmt->execute();
            $ingredients_result = $ingredients_stmt->get_result();
            
            $ingredients = [];
            while ($ingredient_row = $ingredients_result->fetch_assoc()) {
                $ingredients[] = $ingredient_row['ingredient'];
            }
            $row['ingredients'] = implode(', ', $ingredients);
            
            // Get instructions for this recipe
            $instructions_stmt = $conn->prepare("SELECT instruction FROM recipe_instructions WHERE recipe_id = ? ORDER BY step_number");
            $instructions_stmt->bind_param("i", $row['id']);
            $instructions_stmt->execute();
            $instructions_result = $instructions_stmt->get_result();
            
            $instructions = [];
            $step = 1;
            while ($instruction_row = $instructions_result->fetch_assoc()) {
                $instructions[] = $step . '. ' . $instruction_row['instruction'];
                $step++;
            }
            $row['instructions'] = implode("\n", $instructions);
            
            $recipes[] = $row;
        }
        
        echo json_encode(['success' => true, 'recipes' => $recipes]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading recipes: ' . $e->getMessage()]);
    }
}

function getFeaturedRecipes($conn) {
    try {
        // Query with JOIN to get author information for featured recipes
        $sql = "SELECT r.*, a.name as author_name, a.email as author_email 
                FROM recipes r 
                JOIN authors a ON r.author_id = a.id 
                WHERE r.status = 'approved' AND r.featured = 1 
                ORDER BY r.created_at DESC";
        
        $result = $conn->query($sql);
        $recipes = [];
        
        while ($row = $result->fetch_assoc()) {
            // Get ingredients for this recipe
            $ingredients_stmt = $conn->prepare("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ?");
            $ingredients_stmt->bind_param("i", $row['id']);
            $ingredients_stmt->execute();
            $ingredients_result = $ingredients_stmt->get_result();
            
            $ingredients = [];
            while ($ingredient_row = $ingredients_result->fetch_assoc()) {
                $ingredients[] = $ingredient_row['ingredient'];
            }
            $row['ingredients'] = implode(', ', $ingredients);
            
            // Get instructions for this recipe
            $instructions_stmt = $conn->prepare("SELECT instruction FROM recipe_instructions WHERE recipe_id = ? ORDER BY step_number");
            $instructions_stmt->bind_param("i", $row['id']);
            $instructions_stmt->execute();
            $instructions_result = $instructions_stmt->get_result();
            
            $instructions = [];
            $step = 1;
            while ($instruction_row = $instructions_result->fetch_assoc()) {
                $instructions[] = $step . '. ' . $instruction_row['instruction'];
                $step++;
            }
            $row['instructions'] = implode("\n", $instructions);
            
            $recipes[] = $row;
        }
        
        echo json_encode(['success' => true, 'recipes' => $recipes]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading featured recipes: ' . $e->getMessage()]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Recipe Wall - Westley's Resto Café</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    /* Font & Color Variables - Matching about page */
    :root {
      --default-font: "Roboto", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      --heading-font: "Playfair Display", sans-serif;
      --nav-font: "Poppins", sans-serif;
      --background-color: #0c0b09;
      --default-color: rgba(255, 255, 255, 0.7);
      --heading-color: #ffffff;
      --accent-color: #cda45e;
      --surface-color: #29261f;
      --contrast-color: #0c0b09;
    }

    /* General Styles - Matching about page */
    body {
      color: var(--default-color);
      background-color: var(--background-color);
      font-family: var(--default-font);
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }

    a {
      color: var(--accent-color);
      text-decoration: none;
      transition: 0.3s;
    }

    a:hover {
      color: color-mix(in srgb, var(--accent-color), transparent 25%);
    }

    h1, h2, h3, h4, h5, h6 {
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    .container {
      width: 100%;
      max-width: 1140px;
      margin: 0 auto;
      padding: 0 15px;
    }

    .section {
      padding: 60px 0;
    }

    /* Header Styles - Matching about page */
    .header {
      --background-color: rgba(12, 11, 9, 0.61);
      color: var(--default-color);
      transition: all 0.5s;
      z-index: 997;
      position: fixed;
      width: 100%;
      top: 0;
    }

    .header .branding {
      background-color: var(--background-color);
      min-height: 60px;
      padding: 10px 0;
      transition: 0.3s;
      border-bottom: 1px solid var(--background-color);
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header .logo {
      line-height: 1;
      display: flex;
      align-items: center;
    }

    .header .logo img {
      height: 50px;
      margin-right: 15px;
    }

    .header .logo h1 {
      font-size: 24px;
      margin: 0;
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    /* Main Content Padding - Matching about page */
    .main-content {
      padding-top: 80px;
    }

    /* Section Title with Underline Animation - Matching about page */
    .section-title {
      padding-bottom: 60px;
      position: relative;
      text-align: center;
    }

    .section-title h2 {
      font-size: 14px;
      font-weight: 500;
      padding: 30px;
      line-height: 1px;
      margin: 0;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      position: relative;
    }

    .section-title h2::after {
      content: "";
      width: 120px;
      height: 1px;
      display: inline-block;
      background: var(--accent-color);
      margin: 4px 10px;
    }

    .section-title p {
      color: var(--accent-color);
      margin: 15px 0 0;
      font-size: 36px;
      font-weight: 600;
      font-family: var(--heading-font);
      position: relative;
      display: inline-block;
      cursor: pointer;
    }

    .section-title p::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      background: var(--accent-color);
      bottom: -10px;
      left: 0;
      transition: width 0.3s ease;
    }

    .section-title p:hover::after {
      width: 100%;
    }

    /* Recipe Page Specific Styles */
    .nav-tabs {
      display: flex;
      justify-content: center;
      margin: 2rem 0;
      gap: 1rem;
    }

    .tab-btn {
      padding: 12px 24px;
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      font-family: var(--nav-font);
    }

    .tab-btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .tab-btn.active {
      background: var(--surface-color);
      color: var(--accent-color);
    }

    .tab-content {
      display: none;
      animation: fadeIn 0.5s ease-in-out;
    }

    .tab-content.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .recipe-form {
      background: color-mix(in srgb, var(--default-color), transparent 95%);
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
      color: var(--accent-color);
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px;
      background: color-mix(in srgb, var(--default-color), transparent 95%);
      color: var(--default-color);
      border: 2px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      font-family: var(--default-font);
      box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--accent-color);
      box-shadow: 0 0 0 3px rgba(205, 164, 94, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 120px;
    }

    .submit-btn {
      background: var(--accent-color);
      color: var(--contrast-color);
      padding: 15px 30px;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      font-family: var(--nav-font);
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.3);
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    .submit-btn:disabled {
      background: #666;
      cursor: not-allowed;
      transform: none;
    }

    /* Compact Recipe Grid */
    .recipe-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
      margin: 2rem 0;
    }

    .recipe-card {
      background: color-mix(in srgb, var(--default-color), transparent 95%);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      position: relative;
      height: 100%;
      display: flex;
      flex-direction: column;
      animation: fadeInUp 0.5s ease-out;
      animation-fill-mode: both;
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

    .recipe-card:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 12px 30px rgba(0,0,0,0.2);
    }

    .recipe-card.featured {
      border: 2px solid var(--accent-color);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(205, 164, 94, 0.4); }
      70% { box-shadow: 0 0 0 10px rgba(205, 164, 94, 0); }
      100% { box-shadow: 0 0 0 0 rgba(205, 164, 94, 0); }
    }

    .featured-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: var(--accent-color);
      color: var(--contrast-color);
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: bold;
      z-index: 2;
      animation: bounce 2s infinite;
    }

    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
      40% {transform: translateY(-10px);}
      60% {transform: translateY(-5px);}
    }

    .recipe-image {
      width: 100%;
      height: 160px;
      background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #999;
      font-size: 1rem;
      position: relative;
      overflow: hidden;
    }

    .recipe-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .recipe-card:hover .recipe-image img {
      transform: scale(1.1);
    }

    .recipe-content {
      padding: 1rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .recipe-title {
      font-size: 1.1rem;
      font-weight: bold;
      color: var(--accent-color);
      margin-bottom: 0.5rem;
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .recipe-author {
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      font-size: 0.85rem;
      margin-bottom: 0.5rem;
    }

    .recipe-description {
      color: var(--default-color);
      font-size: 0.9rem;
      line-height: 1.4;
      margin-bottom: 1rem;
      flex-grow: 1;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .recipe-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-top: auto;
      padding-top: 0.5rem;
      border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
    }

    .view-btn {
      background: var(--surface-color);
      color: var(--accent-color);
      padding: 6px 12px;
      border: none;
      border-radius: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: var(--nav-font);
      font-size: 0.85rem;
    }

    .view-btn:hover {
      background: var(--accent-color);
      color: var(--contrast-color);
      transform: scale(1.05);
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
      animation: fadeIn 0.3s ease;
    }

    .modal-content {
      background-color: color-mix(in srgb, var(--background-color), transparent 5%);
      margin: 5% auto;
      padding: 2rem;
      border-radius: 15px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      border: 1px solid var(--accent-color);
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .close {
      color: var(--default-color);
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      position: absolute;
      right: 20px;
      top: 15px;
      transition: all 0.3s ease;
    }

    .close:hover {
      color: var(--accent-color);
      transform: rotate(90deg);
    }

    .alert {
      padding: 1rem;
      margin: 1rem 0;
      border-radius: 8px;
      font-weight: bold;
      animation: slideInRight 0.5s ease;
    }

    @keyframes slideInRight {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    .alert.success {
      background: color-mix(in srgb, #d4edda, transparent 80%);
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert.error {
      background: color-mix(in srgb, #f8d7da, transparent 80%);
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .search-bar {
      width: 100%;
      padding: 15px;
      margin-bottom: 2rem;
      background: color-mix(in srgb, var(--default-color), transparent 95%);
      color: var(--default-color);
      border: 2px solid var(--accent-color);
      border-radius: 25px;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }

    .search-bar:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(205, 164, 94, 0.2);
      transform: scale(1.01);
    }

    .filter-tabs {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }

    .filter-btn {
      padding: 8px 16px;
      background: color-mix(in srgb, var(--default-color), transparent 95%);
      color: var(--default-color);
      border: 2px solid var(--accent-color);
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: var(--nav-font);
    }

    .filter-btn:hover,
    .filter-btn.active {
      background: var(--accent-color);
      color: var(--contrast-color);
      transform: translateY(-2px);
    }

    .loading {
      text-align: center;
      padding: 2rem;
      color: var(--default-color);
    }

    .spinner {
      border: 4px solid color-mix(in srgb, var(--default-color), transparent 90%);
      border-top: 4px solid var(--accent-color);
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 1s linear infinite;
      margin: 0 auto 1rem;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .no-recipes {
      text-align: center;
      grid-column: 1/-1;
      padding: 3rem;
      color: var(--default-color);
      animation: fadeIn 0.5s ease;
    }

    /* Stagger animation for recipe cards */
    .recipe-grid .recipe-card:nth-child(1) { animation-delay: 0.1s; }
    .recipe-grid .recipe-card:nth-child(2) { animation-delay: 0.2s; }
    .recipe-grid .recipe-card:nth-child(3) { animation-delay: 0.3s; }
    .recipe-grid .recipe-card:nth-child(4) { animation-delay: 0.4s; }
    .recipe-grid .recipe-card:nth-child(5) { animation-delay: 0.5s; }
    .recipe-grid .recipe-card:nth-child(6) { animation-delay: 0.6s; }
    .recipe-grid .recipe-card:nth-child(7) { animation-delay: 0.7s; }
    .recipe-grid .recipe-card:nth-child(8) { animation-delay: 0.8s; }

    /* Responsive adjustments */
    @media (max-width: 992px) {
      .recipe-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .header .logo h1 {
        font-size: 20px;
      }
      
      .nav-tabs {
        flex-wrap: wrap;
      }
      
      .recipe-grid {
        grid-template-columns: 1fr;
      }
      
      .modal-content {
        margin: 10% auto;
        width: 95%;
        padding: 1.5rem;
      }
      
      .section-title p {
        font-size: 28px;
      }
      
      .recipe-image {
        height: 140px;
      }
    }
  </style>
</head>
<body>
    <!-- Header - Matching about page -->
    <header class="header">
        <div class="branding">
            <div class="container">
                <div class="logo">
                    <img src="img.png" alt="Westley's Resto Cafe">
                    <h1>Westley's Resto Cafe</h1>
                </div>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="container">
            <div class="section-title">
                <h2>COMMUNITY</h2>
                <p>Recipe Wall</p>
            </div>

            <div class="nav-tabs">
                <button class="tab-btn active" onclick="showTab('recipes')">Browse Recipes</button>
                <button class="tab-btn" onclick="showTab('submit')">Submit Recipe</button>
                <button class="tab-btn" onclick="showTab('featured')">Featured</button>
            </div>

            <div id="alert-container"></div>

            <!-- Browse Recipes Tab -->
            <div id="recipes-tab" class="tab-content active">
                <input type="text" class="search-bar" id="searchInput" placeholder="Search recipes by name or description...">
                
                <div class="filter-tabs">
                    <button class="filter-btn active" onclick="filterRecipes('all')">All Recipes</button>
                    <button class="filter-btn" onclick="filterRecipes('recent')">Recent</button>
                    <button class="filter-btn" onclick="filterRecipes('featured')">Featured</button>
                </div>

                <div id="loading" class="loading" style="display: none;">
                    <div class="spinner"></div>
                    <p>Loading delicious recipes...</p>
                </div>

                <div id="recipe-grid" class="recipe-grid">
                    <!-- Recipes will be loaded here -->
                </div>
            </div>

            <!-- Submit Recipe Tab -->
            <div id="submit-tab" class="tab-content">
                <div class="recipe-form">
                    <h2 style="color: var(--accent-color); margin-bottom: 1.5rem; text-align: center;">Share Your Recipe</h2>
                    <form id="recipeForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="authorName"> Name </label>
                            <input type="text" id="authorName" name="authorName" placeholder="Enter Your Name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="authorEmail"> Email </label>
                            <input type="email" id="authorEmail" name="authorEmail" placeholder="Enter Your Email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="recipeTitle">Recipe Name </label>
                            <input type="text" id="recipeTitle" name="recipeTitle" required placeholder="Enter Your Recipe Name">
                        </div>
                        
                        <div class="form-group">
                            <label for="recipeDescription">Short Description</label>
                            <textarea id="recipeDescription" name="recipeDescription" placeholder="Describe your dish in a few words..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="ingredients">Ingredients </label>
                            <textarea id="ingredients" name="ingredients" required placeholder="List all ingredients with measurements (separate with commas)..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="instructions">Cooking Instructions </label>
                            <textarea id="instructions" name="instructions" required placeholder="Step-by-step cooking instructions..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="recipePhoto">Recipe Photo</label>
                            <input type="file" id="recipePhoto" name="recipePhoto" accept="image/*">
                        </div>
                        
                        <button type="submit" class="submit-btn" id="submitBtn">Submit Recipe</button>
                    </form>
                </div>
            </div>

            <!-- Featured Tab -->
            <div id="featured-tab" class="tab-content">
                <h2 style="color: var(--accent-color); text-align: center; margin-bottom: 2rem;">Featured Recipes & Chef of the Month</h2>
                <div id="featured-grid" class="recipe-grid">
                    <!-- Featured recipes will be loaded here -->
                </div>
            </div>
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
        // Global variables
        let allRecipes = [];
        let currentFilter = 'all';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadRecipes();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', debounce(filterAndDisplayRecipes, 300));
            
            // Form submission
            document.getElementById('recipeForm').addEventListener('submit', submitRecipe);
            
            // Modal functionality
            const modal = document.getElementById('recipeModal');
            const closeBtn = document.querySelector('.close');
            
            closeBtn.addEventListener('click', () => modal.style.display = 'none');
            window.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
        }

        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            // Load specific content
            if (tabName === 'featured') {
                loadFeaturedRecipes();
            }
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        async function loadRecipes() {
            showLoading(true);
            try {
                const searchValue = document.getElementById('searchInput').value;
                const formData = new FormData();
                formData.append('action', 'get_recipes');
                formData.append('filter', currentFilter);
                formData.append('search', searchValue);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    allRecipes = data.recipes;
                    displayRecipes(allRecipes);
                } else {
                    showAlert('Error loading recipes: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error loading recipes. Please try again later.', 'error');
            }
            showLoading(false);
        }

        function displayRecipes(recipes) {
            const grid = document.getElementById('recipe-grid');
            
            if (recipes.length === 0) {
                grid.innerHTML = '<div class="no-recipes"><h3>No recipes found</h3><p>Try a different search term or be the first to submit a recipe!</p></div>';
                return;
            }
            
            grid.innerHTML = recipes.map(recipe => `
                <div class="recipe-card ${recipe.featured == '1' ? 'featured' : ''}">
                    ${recipe.featured == '1' ? '<div class="featured-badge">⭐ Featured</div>' : ''}
                    <div class="recipe-image">
                        ${recipe.photo_url ? 
                            `<img src="${recipe.photo_url}" alt="${recipe.title}">` : 
                            '📷 No Photo'
                        }
                    </div>
                    <div class="recipe-content">
                        <div class="recipe-title">${recipe.title}</div>
                        <div class="recipe-author">By: ${recipe.author_name} (${recipe.author_email})</div>
                        <div class="recipe-description">${recipe.description || 'No description available'}</div>
                        <div class="recipe-actions">
                            <button class="view-btn" onclick="viewRecipe(${recipe.id})">View Recipe</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function filterRecipes(type) {
            currentFilter = type;
            
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            loadRecipes();
        }

        function filterAndDisplayRecipes() {
            loadRecipes();
        }

        function viewRecipe(recipeId) {
            const recipe = allRecipes.find(r => r.id == recipeId);
            if (!recipe) return;
            
            const modal = document.getElementById('recipeModal');
            const modalContent = document.getElementById('modalContent');
            
            modalContent.innerHTML = `
                <h2 style="color: var(--accent-color); margin-bottom: 1rem;">${recipe.title}</h2>
                <p style="color: color-mix(in srgb, var(--default-color), transparent 30%); font-style: italic; margin-bottom: 1rem;">Recipe by ${recipe.author_name} (${recipe.author_email})</p>
                
                ${recipe.photo_url ? `<img src="${recipe.photo_url}" alt="${recipe.title}" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;">` : ''}
                
                ${recipe.description ? `<p style="margin-bottom: 1.5rem; font-size: 1.1rem;">${recipe.description}</p>` : ''}
                
                <h3 style="color: var(--accent-color); margin-bottom: 0.5rem;">Ingredients:</h3>
                <div style="background: color-mix(in srgb, var(--default-color), transparent 95%); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    ${recipe.ingredients.split(',').map(ingredient => `<div>• ${ingredient.trim()}</div>`).join('')}
                </div>
                
                <h3 style="color: var(--accent-color); margin-bottom: 0.5rem;">Instructions:</h3>
                <div style="background: color-mix(in srgb, var(--default-color), transparent 95%); padding: 1rem; border-radius: 8px;">
                    ${recipe.instructions.split('\n').filter(step => step.trim()).map((step, index) => 
                        step.trim() ? `<div style="margin-bottom: 0.5rem;">${step.trim()}</div>` : ''
                    ).join('')}
                </div>
            `;
            
            modal.style.display = 'block';
        }

        async function submitRecipe(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const formData = new FormData(event.target);
            formData.append('action', 'submit_recipe');
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    event.target.reset();
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error submitting recipe. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Recipe';
            }
        }

        async function loadFeaturedRecipes() {
            showLoading(true, 'featured-grid');
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_featured'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const grid = document.getElementById('featured-grid');
                    
                    if (data.recipes.length === 0) {
                        grid.innerHTML = '<div class="no-recipes"><h3>No featured recipes yet</h3><p>Check back soon for our monthly featured recipes!</p></div>';
                    } else {
                        grid.innerHTML = data.recipes.map(recipe => `
                            <div class="recipe-card featured">
                                <div class="featured-badge">⭐ Featured</div>
                                <div class="recipe-image">
                                    ${recipe.photo_url ? 
                                        `<img src="${recipe.photo_url}" alt="${recipe.title}">` : 
                                        '📷 No Photo'
                                    }
                                </div>
                                <div class="recipe-content">
                                    <div class="recipe-title">${recipe.title}</div>
                                    <div class="recipe-author">By: ${recipe.author_name} (${recipe.author_email}) - ⭐ Chef of the Month</div>
                                    <div class="recipe-description">${recipe.description || 'No description available'}</div>
                                    <div class="recipe-actions">
                                        <button class="view-btn" onclick="viewRecipe(${recipe.id})">View Recipe</button>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        
                        // Update allRecipes array if needed
                        data.recipes.forEach(featured => {
                            const existingIndex = allRecipes.findIndex(r => r.id == featured.id);
                            if (existingIndex >= 0) {
                                allRecipes[existingIndex] = featured;
                            } else {
                                allRecipes.push(featured);
                            }
                        });
                    }
                } else {
                    showAlert('Error loading featured recipes: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error loading featured recipes.', 'error');
            }
            showLoading(false, 'featured-grid');
        }

        function showLoading(show, containerId = 'recipe-grid') {
            const loading = document.getElementById('loading');
            const container = document.getElementById(containerId);
            
            if (show) {
                if (containerId === 'recipe-grid') {
                    loading.style.display = 'block';
                } else {
                    container.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading featured recipes...</p></div>';
                }
            } else {
                if (containerId === 'recipe-grid') {
                    loading.style.display = 'none';
                }
            }
        }

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
    </script>
</body>
</html>