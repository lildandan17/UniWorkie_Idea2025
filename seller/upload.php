<?php
session_start();

// Use absolute path to ensure the config file is always found
require_once __DIR__ . '/../config.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get database connection
    $conn = getDBConnection();
    
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
        // Handle file uploads
        $image_path = '';
        $file_path = '';
        
        // Process image upload
        // Di bagian proses upload image, ganti dengan kode ini:
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "../uploads/products/images/";
    
    // Buat folder jika belum ada
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true); // true untuk recursive creation
    }
    
    $image_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_name = uniqid('img_') . '.' . $image_ext;
    $image_target = $upload_dir . $image_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $image_target)) {
        $image_path = "uploads/products/images/" . $image_name; // Simpan path relatif
    } else {
        $error = 'Gagal upload gambar. Pastikan folder uploads ada dan bisa ditulisi.';
        // Debug error
        error_log("Upload error: " . print_r(error_get_last(), true));
    }
}
        
        // Process file upload
    
        
        // Insert into database if no errors
        if (empty($error)) {
           $stmt = $conn->prepare("INSERT INTO products (seller_id, title, description, category, price, image_path) 
                      VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssds", $seller_id, $title, $description, $category, $price, $image_path);
            
            if ($stmt->execute()) {
                $success = 'Product uploaded successfully!';
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Product | UniWorkie</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --primary-light: #6BA4E7;
            --primary-lighter: #EDF5FF;
            --primary-dark: #357ABD;
            --secondary: #64B5F6;
            --accent: #2196F3;
            --light-bg: #F8FBFF;
            --white: #ffffff;
            --text-dark: #2C3E50;
            --text-light: #546E7A;
            --shadow-color: rgba(74, 144, 226, 0.1);
            --gradient-start: #4A90E2;
            --gradient-end: #64B5F6;
            --success: #4CAF50;
            --error: #ff6b6b;
            --warning: #FFC107;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: var(--light-bg);
            color: var(--text-dark);
            transition: background-color 0.3s ease;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            height: 100vh;
            position: fixed;
            box-shadow: 4px 0 15px var(--shadow-color);
            padding-top: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .logo {
            color: var(--white);
            font-size: 1.75rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            animation: fadeInDown 0.5s ease;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-menu li a {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            opacity: 0.85;
        }
        
        .nav-menu li a:hover {
            background: rgba(255,255,255,0.15);
            border-left-color: var(--white);
            opacity: 1;
            transform: translateX(5px);
        }
        
        .nav-menu li a.active {
            background: rgba(255,255,255,0.2);
            border-left-color: var(--white);
            opacity: 1;
        }
        
        .nav-menu li a i {
            margin-right: 12px;
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }
        
        .nav-menu li a:hover i {
            transform: scale(1.1);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
            animation: fadeIn 0.5s ease;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            animation: slideDown 0.5s ease;
        }
        
        .page-title {
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            background: var(--primary-lighter);
            border-radius: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .user-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            border: 2px solid var(--primary-light);
            transition: transform 0.3s ease;
        }
        
        .user-profile:hover img {
            transform: scale(1.1);
        }
        
        /* Upload Form */
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease;
        }
        
        .upload-card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease;
        }
        
        .upload-card:hover {
            transform: translateY(-5px);
        }
        
        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .alert i {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .alert-error {
            background-color: rgba(255, 107, 107, 0.1);
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--primary-lighter);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--text-dark);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-lighter);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234A90E2' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 1em;
        }
        
        /* Price Input */
        .price-input {
            position: relative;
        }
        
        .price-input span {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-weight: 600;
        }
        
        .price-input input {
            padding-left: 45px;
        }
        
        /* File Upload */
        .file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border: 2px dashed var(--primary-light);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--primary-lighter);
            position: relative;
            overflow: hidden;
            min-height: 150px;
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(74, 144, 226, 0.1);
        }
        
        .file-upload i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .file-upload:hover i {
            transform: scale(1.1);
        }
        
        .file-upload span {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .file-upload input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-name {
            margin-top: 15px;
            font-size: 0.95rem;
            color: var(--text-light);
            font-weight: 500;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Submit Button */
        .submit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px var(--shadow-color);
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px var(--shadow-color);
        }
        
        .submit-btn i {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        /* Preview Container */
        .preview-container {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            object-fit: contain;
            margin-bottom: 10px;
            border: 2px solid var(--primary-lighter);
            display: none;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .upload-card {
                padding: 20px;
            }
            
            .file-upload {
                padding: 20px;
                min-height: 120px;
            }
            
            .file-upload i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">UniWorkie</div>
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box-open"></i> My Products</a></li>
            <li><a href="upload.php" class="active"><i class="fas fa-upload"></i> Upload Product</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">Upload Product</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>" alt="Profile">
                <span><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Seller'; ?></span>
            </div>
        </div>

        <div class="upload-container">
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
            
            <div class="upload-card">
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title" class="form-label">Product Title</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               required placeholder="e.g. Advanced Calculus Notes">
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" required
                                  placeholder="Describe your product in detail..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">Category</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Select a category</option>
                            <option value="Art & Design" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Art & Design') ? 'selected' : ''; ?>>Art & Design</option>
                            <option value="Information Technology" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                            <option value="Business & Finance" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Business & Finance') ? 'selected' : ''; ?>>Business & Finance</option>
                            <option value="Education" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                            <option value="Health & Medicine" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Health & Medicine') ? 'selected' : ''; ?>>Health & Medicine</option>
                            <option value="Tourism & Services" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Tourism & Services') ? 'selected' : ''; ?>>Tourism & Services</option>
                            <option value="Social Sciences & Psychology" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Social Sciences & Psychology') ? 'selected' : ''; ?>>Social Sciences & Psychology</option>
                            <option value="Engineering" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                            <option value="Law" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Law') ? 'selected' : ''; ?>>Law</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price" class="form-label">Price</label>
                        <div class="price-input">
                            <span>RM</span>
                            <input type="number" id="price" name="price" class="form-control" 
                                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                                   required step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Product Image (Required)</label>
                        <div class="file-upload" id="image-upload">
                            <i class="fas fa-image"></i>
                            <span>Click to upload product image</span>
                            <input type="file" id="image" name="image" accept="image/*">
                            <div class="file-name" id="image-name">No file selected</div>
                        </div>
                        <div class="preview-container">
                            <img id="image-preview" class="image-preview" alt="Image preview">
                        </div>
                    </div>
                    
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-upload"></i> Upload Product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for file upload preview -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image upload handling
            const imageUpload = document.getElementById('image-upload');
            const imageInput = document.getElementById('image');
            const imageName = document.getElementById('image-name');
            const imagePreview = document.getElementById('image-preview');
            
            imageUpload.addEventListener('click', function() {
                imageInput.click();
            });
            
            imageInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    imageName.textContent = this.files[0].name;
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                    imageName.textContent = 'No file selected';
                    imagePreview.style.display = 'none';
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
            
            // Prevent form submission if file is not selected
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                if (!fileInput.files.length) {
                    e.preventDefault();
                    alert('Please upload a product file');
                    fileUpload.style.borderColor = 'var(--error)';
                    fileUpload.style.animation = 'shake 0.5s';
                    
                    setTimeout(() => {
                        fileUpload.style.borderColor = 'var(--primary-light)';
                        fileUpload.style.animation = '';
                    }, 500);
                }
            });
        });
    </script>
</body>
</html>