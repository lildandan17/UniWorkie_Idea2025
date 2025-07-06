<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = (int)$_GET['id'];
$product = null;
$seller_info = null;
$error = '';
$success = '';

// Get DB connection
$conn = getDBConnection();

// Get product details
$stmt = $conn->prepare("
  SELECT p.*, u.name as seller_name, u.email as seller_email, u.phone, u.profile_pic,
       sp.campus, sp.diploma, sp.semester, sp.cgpa, sp.bio
    FROM products p
    JOIN users u ON p.seller_id = u.id
    LEFT JOIN seller_profiles sp ON p.seller_id = sp.user_id
    WHERE p.id = ?
");

$stmt->bind_param("i", $product_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        header('Location: products.php');
        exit();
    }
} else {
    $error = 'Error fetching product details: ' . $stmt->error;
}
$stmt->close();

// Handle add to cart/purchase if user is logged in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $user_id, $product_id);

    if ($stmt->execute()) {
        $success = 'Product added to cart successfully!';
    } else {
        $error = 'Error adding to cart: ' . $stmt->error;
    }
    $stmt->close();
}

$conn->close();


?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['title']); ?> | UniWorkie</title>
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
        
        /* Product Container */
        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
            animation: fadeIn 0.5s ease;
        }
        
        .product-image-container {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            text-align: center;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .product-image-container:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .product-image-container:hover .product-image {
            transform: scale(1.03);
        }
        
        .image-placeholder {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-lighter);
            border-radius: 8px;
            color: var(--primary);
            font-size: 5rem;
        }
        
        .product-info {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease;
        }
        
        .product-info:hover {
            transform: translateY(-5px);
        }
        
        .product-title {
            font-size: 2rem;
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 700;
        }
        
        .product-category {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            box-shadow: 0 3px 8px var(--shadow-color);
        }
        
        .product-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 25px 0;
            display: flex;
            align-items: center;
        }
        
        .product-price:before {
            content: "RM";
            font-size: 1.5rem;
            margin-right: 8px;
            color: var(--text-light);
        }
        
        .product-description {
            line-height: 1.7;
            margin-bottom: 30px;
            color: var(--text-light);
            font-size: 1.1rem;
        }
        
        /* Buttons */
        .btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px var(--shadow-color);
        }
        
        .btn i {
            margin-right: 10px;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            box-shadow: none;
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Seller Info */
        .seller-info {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .seller-info:hover {
            transform: translateY(-5px);
        }
        
        .seller-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .seller-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 25px;
            border: 4px solid var(--primary-light);
            box-shadow: 0 4px 12px var(--shadow-color);
            transition: transform 0.3s ease;
        }
        
        .seller-header:hover .seller-avatar {
            transform: scale(1.05);
        }
        
        .seller-name {
            font-size: 1.6rem;
            margin: 0 0 8px 0;
            color: var(--text-dark);
            font-weight: 700;
        }
        
        .seller-campus {
            color: var(--text-light);
            margin: 0;
            font-size: 1rem;
        }
        
        .seller-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .detail-item {
            background: var(--primary-lighter);
            padding: 15px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .detail-item:hover {
            transform: translateY(-3px);
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
            display: block;
            font-size: 0.9rem;
        }
        
        .seller-bio {
            line-height: 1.7;
            padding: 20px;
            background-color: var(--primary-lighter);
            border-radius: 10px;
            color: var(--text-dark);
            font-size: 1.05rem;
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
            .product-container {
                grid-template-columns: 1fr;
            }
            
            .seller-details {
                grid-template-columns: 1fr;
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
            
            .seller-header {
                flex-direction: column;
                text-align: center;
            }
            
            .seller-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }

        /* Contact Buttons */
.whatsapp-btn {
    background-color: #25D366 !important;
}

.email-btn {
    background-color: #D44638 !important;
}

.contact-buttons {
    display: flex;
    gap: 15px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.btn[disabled] {
    opacity: 0.7;
    cursor: not-allowed;
}

    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">UniWorkie</div>
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="save.php" ><i class="fas fa-heart"></i> Saved Items</a></li>
            <li><a href="recruit.php"><i class="fas fa-user-graduate"></i> Find And Recruit</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">Product Details</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>" alt="Profile">
                <span><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'User'; ?></span>
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

        <div class="product-container">
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
            
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                
                <div class="product-price"><?php echo number_format($product['price'], 2); ?></div>
                
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['seller_id']): ?>
                    <form method="POST" action="productdetails.php?id=<?php echo $product_id; ?>">

                    </form>
                    
                    <?php 
                    $conn = getDBConnection();
                    $check_stmt = $conn->prepare("SELECT * FROM saved_items WHERE buyer_id = ? AND product_id = ?");
                    $check_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
                    $check_stmt->execute();
                    $is_saved = $check_stmt->get_result()->num_rows > 0;
                    $check_stmt->close();
                    $conn->close();
                    ?>
                    
                    <form method="POST" action="save.php" style="display: inline;">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <button type="submit" name="<?php echo $is_saved ? 'remove_product' : 'save_product'; ?>" class="btn btn-outline">
                            <i class="fas fa-heart" style="color: <?php echo $is_saved ? '#ff6b6b' : 'inherit'; ?>"></i> 
                            <?php echo $is_saved ? 'Saved' : 'Save for Later'; ?>
                        </button>
                    </form>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="../login.php" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Login to Purchase
                    </a>
                <?php endif; ?>

                <!-- Inside the product-info div, after the save button -->
<div class="contact-buttons" style="margin-top: 20px;">
    <?php if (!empty($product['phone'])): 
        $phone = preg_replace('/[^0-9]/', '', $product['phone']);
        $phone = ltrim($phone, '0');
        if (substr($phone, 0, 2) !== '60') {
            $phone = '60' . $phone;
        }
        $whatsapp_link = 'https://wa.me/' . $phone;
    ?>
        <a href="<?php echo $whatsapp_link; ?>" class="btn whatsapp-btn" target="_blank" style="background-color: #25D366;">
            <i class="fab fa-whatsapp"></i> Contact via WhatsApp
        </a>
    <?php else: ?>
        <button class="btn whatsapp-btn" disabled style="background-color: #cccccc;">
            <i class="fab fa-whatsapp"></i> WhatsApp Not Available
        </button>
    <?php endif; ?>
    
    <?php if (!empty($product['seller_email'])): 
        $email_link = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . $product['seller_email'];
    ?>
        <a href="<?php echo $email_link; ?>" class="btn email-btn" style="background-color: #D44638; margin-left: 10px;">
            <i class="fas fa-envelope"></i> Contact via Email
        </a>
    <?php else: ?>
        <button class="btn email-btn" disabled style="background-color: #cccccc; margin-left: 10px;">
            <i class="fas fa-envelope"></i> Email Not Available
        </button>
    <?php endif; ?>
</div>


            </div>
        </div>
        
        <!-- Seller Information -->
        <div class="seller-info">
            <div class="seller-header">
                <img src="../uploads/<?php echo htmlspecialchars($product['profile_pic'] ?? 'default-avatar.jpg'); ?>" alt="Seller" class="seller-avatar">
                <div>
                    <h2 class="seller-name"><?php echo htmlspecialchars($product['seller_name']); ?></h2>
                    <p class="seller-campus"><?php echo htmlspecialchars($product['campus'] ?? 'Unknown Campus'); ?></p>
                </div>
            </div>
            
            <div class="seller-details">
                <div class="detail-item">
                    <span class="detail-label">Diploma</span>
                    <div><?php echo htmlspecialchars($product['diploma'] ?? 'Not specified'); ?></div>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Semester</span>
                    <div><?php echo htmlspecialchars($product['semester'] ?? 'Not specified'); ?></div>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">CGPA</span>
                    <div><?php echo htmlspecialchars($product['cgpa'] ?? 'Not specified'); ?></div>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Contact</span>
                    <div><?php echo htmlspecialchars($product['seller_email']); ?></div>
                </div>
            </div>
            
            <?php if (!empty($product['bio'])): ?>
                <h3>About the Seller</h3>
                <div class="seller-bio">
                    <?php echo nl2br(htmlspecialchars($product['bio'])); ?>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any interactive elements here
            console.log('Product details page loaded');
        });

        

    </script>
</body>
</html>