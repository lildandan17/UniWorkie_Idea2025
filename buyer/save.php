<?php
session_start();
require_once __DIR__ . '/../config.php';

// Redirect if not buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header('Location: ../login.php');
    exit();
}

$buyer_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle add/remove from saved items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    if (isset($_POST['save_product'])) {
        $product_id = (int)$_POST['product_id'];
        
        // Check if already saved
        $check_stmt = $conn->prepare("SELECT * FROM saved_items WHERE buyer_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $buyer_id, $product_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'This product is already in your saved items';
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO saved_items (buyer_id, product_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $buyer_id, $product_id);
            
            if ($insert_stmt->execute()) {
                $success = 'Product saved successfully!';
            } else {
                $error = 'Error saving product: ' . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } elseif (isset($_POST['remove_product'])) {
        $product_id = (int)$_POST['product_id'];
        
        $delete_stmt = $conn->prepare("DELETE FROM saved_items WHERE buyer_id = ? AND product_id = ?");
        $delete_stmt->bind_param("ii", $buyer_id, $product_id);
        
        if ($delete_stmt->execute()) {
            $success = 'Product removed from saved items';
        } else {
            $error = 'Error removing product: ' . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
    $conn->close();
}

// Fetch saved products
$saved_products = [];
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT p.*, u.name as seller_name 
    FROM saved_items s
    JOIN products p ON s.product_id = p.id
    JOIN users u ON p.seller_id = u.id
    WHERE s.buyer_id = ?
");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$saved_products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Saved Items | UniWorkie</title>
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
            --error: #ff6b6b;
            --success: #4CAF50;
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
            margin-bottom: 20px;
            animation: fadeInLeft 0.5s ease;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            animation: fadeIn 0.5s ease;
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
        
        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
            margin-top: 20px;
            animation: fadeIn 0.5s ease;
        }
        
        .product-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: all 0.3s ease;
            position: relative;
            top: 0;
        }
        
        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .product-image {
            height: 180px;
            background-color: var(--primary-lighter);
            background-size: cover;
            background-position: center;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.1);
        }
        
        .product-info {
            padding: 20px;
            background: var(--white);
            position: relative;
        }
        
        .product-title {
            font-weight: 600;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 1.1rem;
            color: var(--text-dark);
        }
        
        .product-price {
            color: var(--primary);
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 8px;
        }
        
        .product-seller {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        /* Remove Button */
        .remove-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            color: var(--error);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            z-index: 2;
        }
        
        .remove-btn:hover {
            background: var(--error);
            color: white;
            transform: scale(1.1);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-light);
            animation: fadeIn 0.5s ease;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--primary-lighter);
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .empty-state p {
            font-size: 1.2rem;
            margin-bottom: 25px;
            color: var(--text-light);
        }
        
        .empty-state .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .empty-state .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px var(--shadow-color);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--primary-lighter);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 20px;
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
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">UniWorkie</div>
        <ul class="nav-menu">
          <li><a href="dashboard.php" ><i class="fas fa-home"></i> Home</a></li>
            <li><a href="save.php" class="active"><i class="fas fa-heart"></i> Saved Items</a></li>
            <li><a href="recruit.php"><i class="fas fa-user-graduate"></i> Find And Recruit</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">My Saved Items</h1>
        
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
        
        <?php if (empty($saved_products)): ?>
            <div class="empty-state">
                <i class="fas fa-heart"></i>
                <p>You haven't saved any items yet</p>
                <a href="dashboard.php" class="btn">
                    <i class="fas fa-search"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($saved_products as $product): ?>
                    <div class="product-card">
                        <form method="POST" action="save.php" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" name="remove_product" class="remove-btn" title="Remove from saved items">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                        <div class="product-image" style="background-image: url('../uploads/products/images/<?php echo !empty($product['image_path']) ? basename($product['image_path']) : 'default_product.jpg'; ?>')"
                             onclick="window.location.href='productdetails.php?id=<?php echo $product['id']; ?>'"></div>
                        <div class="product-info" onclick="window.location.href='productdetails.php?id=<?php echo $product['id']; ?>'">
                            <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                            <div class="product-seller">By <?php echo htmlspecialchars($product['seller_name']); ?></div>
                            <div class="product-price">RM <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>