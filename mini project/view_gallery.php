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
    // Handle carousel operations
    if (isset($_POST['carousel_action'])) {
        handleCarouselAction($conn);
    }
    
    // Handle grid operations
    if (isset($_POST['grid_action'])) {
        handleGridAction($conn);
    }
}

function handleCarouselAction($conn) {
    $action = $_POST['carousel_action'];
    
    switch ($action) {
        case 'add':
            $image_url = $conn->real_escape_string($_POST['carousel_image_url']);
            $title = $conn->real_escape_string($_POST['carousel_title']);
            $caption = $conn->real_escape_string($_POST['carousel_caption']);
            
            $sql = "INSERT INTO gallery_carousel (image_url, title, caption) VALUES ('$image_url', '$title', '$caption')";
            $conn->query($sql);
            break;
            
        case 'update':
            $id = (int)$_POST['carousel_id'];
            $image_url = $conn->real_escape_string($_POST['carousel_image_url']);
            $title = $conn->real_escape_string($_POST['carousel_title']);
            $caption = $conn->real_escape_string($_POST['carousel_caption']);
            
            $sql = "UPDATE gallery_carousel SET image_url='$image_url', title='$title', caption='$caption' WHERE id=$id";
            $conn->query($sql);
            break;
            
        case 'delete':
            $id = (int)$_POST['carousel_id'];
            $sql = "DELETE FROM gallery_carousel WHERE id=$id";
            $conn->query($sql);
            break;
            
        case 'reorder':
            if (isset($_POST['carousel_order'])) {
                $order = $_POST['carousel_order'];
                foreach ($order as $position => $id) {
                    $id = (int)$id;
                    $position = (int)$position;
                    $sql = "UPDATE gallery_carousel SET display_order=$position WHERE id=$id";
                    $conn->query($sql);
                }
            }
            break;
    }
}

function handleGridAction($conn) {
    $action = $_POST['grid_action'];
    
    switch ($action) {
        case 'add':
            $image_url = $conn->real_escape_string($_POST['grid_image_url']);
            $title = $conn->real_escape_string($_POST['grid_title']);
            $caption = $conn->real_escape_string($_POST['grid_caption']);
            
            $sql = "INSERT INTO gallery_grid (image_url, title, caption) VALUES ('$image_url', '$title', '$caption')";
            $conn->query($sql);
            break;
            
        case 'update':
            $id = (int)$_POST['grid_id'];
            $image_url = $conn->real_escape_string($_POST['grid_image_url']);
            $title = $conn->real_escape_string($_POST['grid_title']);
            $caption = $conn->real_escape_string($_POST['grid_caption']);
            
            $sql = "UPDATE gallery_grid SET image_url='$image_url', title='$title', caption='$caption' WHERE id=$id";
            $conn->query($sql);
            break;
            
        case 'delete':
            $id = (int)$_POST['grid_id'];
            $sql = "DELETE FROM gallery_grid WHERE id=$id";
            $conn->query($sql);
            break;
            
        case 'reorder':
            if (isset($_POST['grid_order'])) {
                $order = $_POST['grid_order'];
                foreach ($order as $position => $id) {
                    $id = (int)$id;
                    $position = (int)$position;
                    $sql = "UPDATE gallery_grid SET display_order=$position WHERE id=$id";
                    $conn->query($sql);
                }
            }
            break;
    }
}

// Get current carousel and grid items
$carousel_items = $conn->query("SELECT * FROM gallery_carousel ORDER BY display_order ASC");
$grid_items = $conn->query("SELECT * FROM gallery_grid ORDER BY display_order ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - Westley's Resto Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root{
      --primary:#2d3748; --light:#f7fafc; --lighter:#fff;
      --border:#e2e8f0; --radius:.375rem; --shadow:0 1px 3px rgba(0,0,0,.08);
      --success:#38a169; --danger:#e53e3e; --warning:#dd6b20;
      --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    *{box-sizing:border-box}
    body{font-family:'Inter',sans-serif;background:var(--light);color:var(--primary);padding:2rem}
    .container{max-width:1200px;margin:0 auto}
    .header{display:flex;flex-direction:column;align-items:center;margin-bottom:1.5rem;position:relative}
    .header h1{font-size:1.8rem;margin-bottom:1rem;animation:fadeIn 0.5s ease-out;text-align:center}
    
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
    .item-preview {
        flex: 0 0 200px;
    }
    .item-preview img {
        width: 100%;
        height: auto;
        border-radius: var(--radius);
    }
    .item-details {
        flex: 1;
    }
    .item-details h3 {
        margin-top: 0;
        margin-bottom: 0.5rem;
    }
    .item-details p {
        margin-top: 0;
        color: #4a5568;
    }
    
    /* Sortable Lists */
    .sortable-list {
        list-style-type: none;
        padding: 0;
    }
    .sortable-item {
        background: var(--lighter);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem;
        margin-bottom: 0.75rem;
        cursor: move;
        display: flex;
        align-items: center;
        transition: var(--transition);
    }
    .sortable-item:hover {
        background: rgba(237,242,247,0.7);
    }
    .sortable-item i {
        margin-right: 1rem;
        color: var(--primary);
    }
    
    /* Alerts */
    .alert {
        padding: 1rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        animation: fadeIn 0.5s ease-out;
    }
    .alert-success {
        background: rgba(56,161,105,0.08);
        border-left: 4px solid var(--success);
        color: var(--success);
    }
    .alert-error {
        background: rgba(229,62,62,0.06);
        border-left: 4px solid var(--danger);
        color: var(--danger);
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
    
    /* Modals */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background-color: white;
        padding: 2rem;
        border-radius: var(--radius);
        width: 90%;
        max-width: 600px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .modal-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px) }
        to { opacity: 1; transform: translateY(0) }
    }
    @keyframes spin {
        0% { transform: rotate(0deg) }
        100% { transform: rotate(360deg) }
    }
    
    @media (max-width: 768px) {
        body { padding: 1rem }
        .item-preview { flex: 0 0 100% }
        .item-card { flex-direction: column }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gallery Management</h1>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="carousel">Main Carousel</div>
            <div class="tab" data-tab="grid">Gallery Grid</div>
        </div>
        
        <!-- Carousel Tab -->
        <div class="tab-content active" id="carousel-tab">
            <div class="card">
                <h2>Add New Carousel Item</h2>
                <form method="POST" action="view_gallery.php">
                    <div class="form-group">
                        <label for="carousel_image_url">Image URL</label>
                        <input type="text" id="carousel_image_url" name="carousel_image_url" required>
                    </div>
                    <div class="form-group">
                        <label for="carousel_title">Title</label>
                        <input type="text" id="carousel_title" name="carousel_title" required>
                    </div>
                    <div class="form-group">
                        <label for="carousel_caption">Caption</label>
                        <textarea id="carousel_caption" name="carousel_caption" required></textarea>
                    </div>
                    <input type="hidden" name="carousel_action" value="add">
                    <button type="submit" class="btn" style="background: var(--primary); color: white;">Add to Carousel</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Manage Carousel Items</h2>
                <div class="item-list">
                    <?php if ($carousel_items->num_rows > 0): ?>
                        <form method="POST" action="view_gallery.php" id="carousel-reorder-form">
                            <ul class="sortable-list" id="carousel-sortable">
                                <?php while ($item = $carousel_items->fetch_assoc()): ?>
                                    <li class="sortable-item" data-id="<?php echo $item['id']; ?>">
                                        <i class="fas fa-arrows-alt"></i>
                                        <div class="item-card">
                                            <div class="item-preview">
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                            </div>
                                            <div class="item-details">
                                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                                <p><?php echo htmlspecialchars($item['caption']); ?></p>
                                                <div class="item-actions">
                                                    <button type="button" class="btn btn-secondary edit-carousel-btn" 
                                                            data-id="<?php echo $item['id']; ?>"
                                                            data-image="<?php echo htmlspecialchars($item['image_url']); ?>"
                                                            data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                            data-caption="<?php echo htmlspecialchars($item['caption']); ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-danger delete-carousel-btn" data-id="<?php echo $item['id']; ?>">
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                            <input type="hidden" name="carousel_action" value="reorder">
                            <input type="hidden" name="carousel_order" id="carousel_order" value="">
                            <button type="submit" class="btn" id="save-carousel-order" style="display: none; background: var(--primary); color: white;">Save Order</button>
                        </form>
                    <?php else: ?>
                        <p>No carousel items found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Grid Tab -->
        <div class="tab-content" id="grid-tab">
            <div class="card">
                <h2>Add New Gallery Grid Item</h2>
                <form method="POST" action="view_gallery.php">
                    <div class="form-group">
                        <label for="grid_image_url">Image URL</label>
                        <input type="text" id="grid_image_url" name="grid_image_url" required>
                    </div>
                    <div class="form-group">
                        <label for="grid_title">Title</label>
                        <input type="text" id="grid_title" name="grid_title" required>
                    </div>
                    <div class="form-group">
                        <label for="grid_caption">Caption</label>
                        <textarea id="grid_caption" name="grid_caption" required></textarea>
                    </div>
                    <input type="hidden" name="grid_action" value="add">
                    <button type="submit" class="btn" style="background: var(--primary); color: white;">Add to Gallery Grid</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Manage Gallery Grid Items</h2>
                <div class="item-list">
                    <?php if ($grid_items->num_rows > 0): ?>
                        <form method="POST" action="view_gallery.php" id="grid-reorder-form">
                            <ul class="sortable-list" id="grid-sortable">
                                <?php while ($item = $grid_items->fetch_assoc()): ?>
                                    <li class="sortable-item" data-id="<?php echo $item['id']; ?>">
                                        <i class="fas fa-arrows-alt"></i>
                                        <div class="item-card">
                                            <div class="item-preview">
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                            </div>
                                            <div class="item-details">
                                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                                <p><?php echo htmlspecialchars($item['caption']); ?></p>
                                                <div class="item-actions">
                                                    <button type="button" class="btn btn-secondary edit-grid-btn" 
                                                            data-id="<?php echo $item['id']; ?>"
                                                            data-image="<?php echo htmlspecialchars($item['image_url']); ?>"
                                                            data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                            data-caption="<?php echo htmlspecialchars($item['caption']); ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-danger delete-grid-btn" data-id="<?php echo $item['id']; ?>">
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                            <input type="hidden" name="grid_action" value="reorder">
                            <input type="hidden" name="grid_order" id="grid_order" value="">
                            <button type="submit" class="btn" id="save-grid-order" style="display: none; background: var(--primary); color: white;">Save Order</button>
                        </form>
                    <?php else: ?>
                        <p>No gallery grid items found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Edit Carousel Modal -->
        <div id="editCarouselModal" class="modal">
            <div class="modal-content">
                <h2>Edit Carousel Item</h2>
                <form method="POST" action="view_gallery.php" id="edit-carousel-form">
                    <input type="hidden" name="carousel_id" id="edit_carousel_id">
                    <input type="hidden" name="carousel_action" value="update">
                    <div class="form-group">
                        <label for="edit_carousel_image_url">Image URL</label>
                        <input type="text" id="edit_carousel_image_url" name="carousel_image_url" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_carousel_title">Title</label>
                        <input type="text" id="edit_carousel_title" name="carousel_title" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_carousel_caption">Caption</label>
                        <textarea id="edit_carousel_caption" name="carousel_caption" required></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn" style="background: var(--primary); color: white;">Save Changes</button>
                        <button type="button" class="btn btn-secondary" id="cancel-edit-carousel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Edit Grid Modal -->
        <div id="editGridModal" class="modal">
            <div class="modal-content">
                <h2>Edit Gallery Grid Item</h2>
                <form method="POST" action="view_gallery.php" id="edit-grid-form">
                    <input type="hidden" name="grid_id" id="edit_grid_id">
                    <input type="hidden" name="grid_action" value="update">
                    <div class="form-group">
                        <label for="edit_grid_image_url">Image URL</label>
                        <input type="text" id="edit_grid_image_url" name="grid_image_url" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_grid_title">Title</label>
                        <input type="text" id="edit_grid_title" name="grid_title" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_grid_caption">Caption</label>
                        <textarea id="edit_grid_caption" name="grid_caption" required></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn" style="background: var(--primary); color: white;">Save Changes</button>
                        <button type="button" class="btn btn-secondary" id="cancel-edit-grid">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Deletion</h2>
                <p>Are you sure you want to delete this item?</p>
                <form method="POST" action="view_gallery.php" id="delete-form">
                    <input type="hidden" name="carousel_id" id="delete_id">
                    <input type="hidden" name="carousel_action" id="delete_action" value="delete">
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" id="cancel-delete">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            // Tab switching
            $('.tab').on('click', function() {
                const tabId = $(this).data('tab');
                $('.tab').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $(`#${tabId}-tab`).addClass('active');
            });
            
            // Make lists sortable
            $('#carousel-sortable, #grid-sortable').sortable({
                handle: '.sortable-item',
                update: function(event, ui) {
                    const listId = $(this).attr('id');
                    const order = $(this).sortable('toArray', { attribute: 'data-id' });
                    $(`#${listId.replace('sortable', 'order')}`).val(order.join(','));
                    
                    if (listId === 'carousel-sortable') {
                        $('#save-carousel-order').show();
                    } else {
                        $('#save-grid-order').show();
                    }
                }
            });
            
            // Edit carousel item
            $('.edit-carousel-btn').on('click', function() {
                const id = $(this).data('id');
                const image = $(this).data('image');
                const title = $(this).data('title');
                const caption = $(this).data('caption');
                
                $('#edit_carousel_id').val(id);
                $('#edit_carousel_image_url').val(image);
                $('#edit_carousel_title').val(title);
                $('#edit_carousel_caption').val(caption);
                
                $('#editCarouselModal').css('display', 'flex');
            });
            
            // Edit grid item
            $('.edit-grid-btn').on('click', function() {
                const id = $(this).data('id');
                const image = $(this).data('image');
                const title = $(this).data('title');
                const caption = $(this).data('caption');
                
                $('#edit_grid_id').val(id);
                $('#edit_grid_image_url').val(image);
                $('#edit_grid_title').val(title);
                $('#edit_grid_caption').val(caption);
                
                $('#editGridModal').css('display', 'flex');
            });
            
            // Delete item
            $('.delete-carousel-btn, .delete-grid-btn').on('click', function() {
                const id = $(this).data('id');
                const isCarousel = $(this).hasClass('delete-carousel-btn');
                
                $('#delete_id').val(id);
                if (isCarousel) {
                    $('#delete_action').val('carousel_id');
                    $('#delete_action').attr('name', 'carousel_id');
                    $('#delete-form input[name="carousel_action"]').val('delete');
                } else {
                    $('#delete_action').val('grid_id');
                    $('#delete_action').attr('name', 'grid_id');
                    $('#delete-form input[name="grid_action"]').val('delete');
                    $('#delete-form input[name="carousel_action"]').remove();
                }
                
                $('#deleteModal').css('display', 'flex');
            });
            
            // Cancel edit carousel
            $('#cancel-edit-carousel').on('click', function() {
                $('#editCarouselModal').hide();
            });
            
            // Cancel edit grid
            $('#cancel-edit-grid').on('click', function() {
                $('#editGridModal').hide();
            });
            
            // Cancel delete
            $('#cancel-delete').on('click', function() {
                $('#deleteModal').hide();
            });
            
            // Save order
            $('#save-carousel-order, #save-grid-order').on('click', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                form.submit();
            });
        });
    </script>
</body>
</html>