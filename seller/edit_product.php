<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];
$error = '';
$success = '';
$product = [];

// Get product ID from URL
if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $price = trim($_POST['price']);
    
    // Validate inputs
    if (empty($title) || empty($description) || empty($category) || empty($price)) {
        $error = 'All fields are required';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Price must be a valid positive number';
    } else {
        // Initialize variables for file paths
        $image_path = $product['image_path'] ?? ''; // Use null coalescing operator to handle missing key
        
        // Process image upload if new image is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/products/images/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $image_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('img_') . '.' . $image_ext;
            $image_target = $upload_dir . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_target)) {
                // Delete old image if it exists
                if (!empty($product['image_path']) && file_exists("../" . $product['image_path'])) {
                    unlink("../" . $product['image_path']);
                }
                $image_path = "uploads/products/images/" . $image_name;
            } else {
                $error = 'Failed to upload image. Please try again.';
            }
        }
        
        // Update database if no errors
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE products SET title = ?, description = ?, category = ?, price = ?, image_path = ? WHERE id = ?");
            // Corrected type string - 6 parameters: s (title), s (description), s (category), d (price), s (image_path), i (id)
            $stmt->bind_param("sssdsi", $title, $description, $category, $price, $image_path, $product_id);
            
            if ($stmt->execute()) {
                $success = 'Product updated successfully!';
                // Refresh product data
                $product['title'] = $title;
                $product['description'] = $description;
                $product['category'] = $category;
                $product['price'] = $price;
                $product['image_path'] = $image_path;
            } else {
                $error = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Product | UniWorkie</title>
    <style>
        /* Keep all the same styles from upload.php */
        :root {
            --primary: #61B0FE;
            --secondary: #508ec6;
            --light-bg: #f5f7fa;
            --white: #ffffff;
            --text-dark: #333333;
            --text-light: #555555;
            --error: #e74c3c;
            --success: #2ecc71;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-bg);
            color: var(--text-dark);
        }
        
        /* Navigation */
        .sidebar {
            width: 250px;
            background: var(--white);
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding-top: 20px;
        }
        
        .logo {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
        }
        
        .nav-menu li a {
            display: block;
            padding: 12px 20px;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-menu li a:hover, 
        .nav-menu li a.active {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .nav-menu li a i {
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Edit Form */
        .edit-form {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }
        
        .file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background-color: rgba(97, 176, 254, 0.05);
        }
        
        .file-upload i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .file-upload input {
            display: none;
        }
        
        .file-name {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .price-input {
            position: relative;
        }
        
        .price-input span {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .price-input input {
            padding-left: 40px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .current-file {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .current-file a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .current-file a:hover {
            text-decoration: underline;
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">UniWorkie</div>
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box-open"></i> My Products</a></li>
            <li><a href="upload.php"><i class="fas fa-upload"></i> Upload Product</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>Edit Product</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>" alt="Profile">
                <span><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Seller'; ?></span>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="edit-form">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Product Title</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($product['title']); ?>" 
                           required placeholder="e.g. Advanced Calculus Notes">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required
                              placeholder="Describe your product in detail..."><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select a category</option>
                        <option value="Art & Design" <?php echo ($product['category'] === 'Art & Design') ? 'selected' : ''; ?>>Art & Design</option>
                        <option value="Information Technology" <?php echo ($product['category'] === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Business & Finance" <?php echo ($product['category'] === 'Business & Finance') ? 'selected' : ''; ?>>Business & Finance</option>
                        <option value="Education" <?php echo ($product['category'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                        <option value="Health & Medicine" <?php echo ($product['category'] === 'Health & Medicine') ? 'selected' : ''; ?>>Health & Medicine</option>
                        <option value="Tourism & Services" <?php echo ($product['category'] === 'Tourism & Services') ? 'selected' : ''; ?>>Tourism & Services</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (RM)</label>
                    <div class="price-input">
                        <span>RM</span>
                        <input type="number" id="price" name="price" class="form-control" 
                               value="<?php echo htmlspecialchars($product['price']); ?>" 
                               required step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Product Image (Optional)</label>
                    <div class="file-upload" id="image-upload">
                        <i class="fas fa-image"></i>
                        <span>Click to change product image</span>
                        <input type="file" id="image" name="image" accept="image/*">
                        <div class="file-name" id="image-name">No file selected</div>
                    </div>
                    <?php if (!empty($product['image_path'])): ?>
                        <div class="current-file">
                            Current image: <a href="../<?php echo htmlspecialchars($product['image_path']); ?>" target="_blank">View Image</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for file upload preview -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image upload handling
            const imageUpload = document.getElementById('image-upload');
            const imageInput = document.getElementById('image');
            const imageName = document.getElementById('image-name');
            
            imageUpload.addEventListener('click', function() {
                imageInput.click();
            });
            
            imageInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    imageName.textContent = this.files[0].name;
                } else {
                    imageName.textContent = 'No file selected';
                }
            });
            
            // File upload handling
            const fileUpload = document.getElementById('file-upload');
            const fileInput = document.getElementById('product_file');
            const fileName = document.getElementById('file-name');
            
            fileUpload.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileName.textContent = this.files[0].name;
                } else {
                    fileName.textContent = 'No file selected';
                }
            });
        });
    </script>
</body>
</html>