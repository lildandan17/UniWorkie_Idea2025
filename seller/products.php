<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];
$conn = getDBConnection();
$error = $success = '';

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // First delete related saved items
    $delete_saved = $conn->prepare("DELETE FROM saved_items WHERE product_id = ?");
    $delete_saved->bind_param("i", $product_id);
    $delete_saved->execute();
    $delete_saved->close();
    
    // Then delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);
    
    if ($stmt->execute()) {
        $success = "Product deleted successfully!";
    } else {
        $error = "Error deleting product: " . $conn->error;
    }
    $stmt->close();

}

// Get all products for this seller
$products = [];
$stmt = $conn->prepare("SELECT id, title, price, image_path, description, category, created_at FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products | UniWorkie</title>
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
        
        /* Add Product Button */
        .add-product-btn {
            display: inline-flex;
            align-items: center;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 4px 12px var(--shadow-color);
            margin-bottom: 30px;
            text-decoration: none;
        }
        
        .add-product-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px var(--shadow-color);
        }
        
        .add-product-btn i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        /* Products Table */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease;
        }
        
        .products-table:hover {
            transform: translateY(-5px);
        }
        
        .products-table th {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 18px;
            text-align: left;
            font-weight: 600;
        }
        
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid var(--primary-lighter);
        }
        
        .products-table tr:last-child td {
            border-bottom: none;
        }
        
        .products-table tr:hover {
            background-color: var(--primary-lighter);
        }
        
        /* Product Image */
        .product-image-container {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-lighter);
        }
        
        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        
        .products-table tr:hover .product-image {
            transform: scale(1.1);
        }
        
        .image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        /* Product Info */
        .product-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .product-category {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .product-price {
            font-weight: 600;
            color: var(--primary);
        }
        
        .product-price:before {
            content: "RM";
            font-size: 0.9rem;
            margin-right: 5px;
            color: var(--text-light);
        }
        
        .product-description {
            font-size: 0.95rem;
            color: var(--text-light);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn i {
            margin-right: 5px;
            font-size: 0.9rem;
        }
        
        .btn-edit {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-edit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px var(--shadow-color);
        }
        
        .btn-delete {
            background-color: var(--error);
            color: white;
            border: none;
        }
        
        .btn-delete:hover {
            background-color: #e53935;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(239, 83, 80, 0.2);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--primary-lighter);
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .empty-state p {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 25px;
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
        @media (max-width: 992px) {
            .products-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
            
            .products-table th, 
            .products-table td {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
            
            .product-image-container {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">UniWorkie</div>
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box-open"></i> My Products</a></li>
            <li><a href="upload.php"><i class="fas fa-upload"></i> Upload Product</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">My Products</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>" alt="Profile">
                <span><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Seller'; ?></span>
            </div>
        </div>

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

        <a href="upload.php" class="add-product-btn">
            <i class="fas fa-plus"></i> Add New Product
        </a>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>You haven't uploaded any products yet</p>
                <a href="upload.php" class="add-product-btn">
                    <i class="fas fa-plus"></i> Upload Your First Product
                </a>
            </div>
        <?php else: ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="product-image-container">
                                    <?php if (!empty($product['image_path'])): ?>
                                        <?php
                                        $imagePath = $product['image_path'];
                                        if (strpos($imagePath, 'uploads/') === false) {
                                            $imagePath = 'uploads/products/' . $imagePath;
                                        }
                                        ?>
                                        <img src="../<?php echo htmlspecialchars($imagePath); ?>" 
                                            alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                            class="product-image"
                                            onerror="this.src='../uploads/default-product.jpg'">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                                <div class="product-category"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></div>
                            </td>
                            <td>
                                <div class="product-price"><?php echo number_format($product['price'], 2); ?></div>
                            </td>
                            <td>
                                <div class="product-description" title="<?php echo htmlspecialchars($product['description']); ?>">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this product?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any interactive elements here
            console.log('Products page loaded');
        });
    </script>
</body>
</html>