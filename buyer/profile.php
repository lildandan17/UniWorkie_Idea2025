<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in as buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: ../login.php');
    exit();
}

// Check if seller ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: browse.php');
    exit();
}

$seller_id = (int)$_GET['id'];
$seller = null;
$products = [];
$error = '';

try {
    // Get seller details
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.name, u.email, u.created_at, 
               sp.campus, sp.diploma, sp.semester, sp.cgpa, sp.bio
        FROM users u
        LEFT JOIN seller_profiles sp ON u.user_id = sp.user_id
        WHERE u.user_id = ? AND u.user_type = 'seller'
    ");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller) {
        header('Location: browse.php');
        exit();
    }

    // Get seller's products
    $stmt = $pdo->prepare("
        SELECT id, title, price, created_at, image_path 
        FROM products 
        WHERE seller_id = ? 
        ORDER BY created_at DESC 
        LIMIT 6
    ");
    $stmt->execute([$seller_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching seller details: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($seller['name']); ?>'s Profile | UniWorkie</title>
    <style>
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
        
        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: center;
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 5px solid var(--primary);
        }
        
        .profile-info h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .profile-campus {
            color: var(--text-light);
            margin: 0 0 15px 0;
            font-size: 1.1rem;
        }
        
        .member-since {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* Profile Details */
        .profile-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .detail-card h2 {
            margin-top: 0;
            font-size: 1.3rem;
            color: var(--primary);
            border-bottom: 2px solid var(--light-bg);
            padding-bottom: 10px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--text-light);
            margin-bottom: 5px;
            display: block;
        }
        
        .bio-content {
            line-height: 1.6;
            padding: 15px;
            background-color: var(--light-bg);
            border-radius: 5px;
        }
        
        /* Seller Products */
        .products-section {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .products-section h2 {
            margin-top: 0;
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 180px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            font-weight: 600;
            margin: 0 0 5px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .product-price {
            color: var(--primary);
            font-weight: bold;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .view-all {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        /* Alerts */
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
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <h1>Seller Profile</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>" alt="Profile">
                <span><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Buyer'; ?></span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <img src="../uploads/default-avatar.jpg" alt="Seller" class="profile-avatar">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($seller['name']); ?></h1>
                <p class="profile-campus"><?php echo htmlspecialchars($seller['campus'] ?? 'Unknown Campus'); ?></p>
                <p class="member-since">Member since <?php echo date('F Y', strtotime($seller['created_at'])); ?></p>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="profile-details">
            <div class="detail-card">
                <h2>Academic Information</h2>
                <div class="detail-item">
                    <span class="detail-label">Diploma/Degree</span>
                    <div><?php echo htmlspecialchars($seller['diploma'] ?? 'Not specified'); ?></div>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Semester</span>
                    <div><?php echo htmlspecialchars($seller['semester'] ?? 'Not specified'); ?></div>
                </div>
                <div class="detail-item">
                    <span class="detail-label">CGPA</span>
                    <div><?php echo htmlspecialchars($seller['cgpa'] ?? 'Not specified'); ?></div>
                </div>
            </div>
            
            <div class="detail-card">
                <h2>Contact Information</h2>
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <div><?php echo htmlspecialchars($seller['email']); ?></div>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Campus</span>
                    <div><?php echo htmlspecialchars($seller['campus'] ?? 'Not specified'); ?></div>
                </div>
            </div>
            
            <?php if (!empty($seller['bio'])): ?>
                <div class="detail-card" style="grid-column: span 2;">
                    <h2>About</h2>
                    <div class="bio-content">
                        <?php echo nl2br(htmlspecialchars($seller['bio'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Seller Products -->
        <div class="products-section">
            <h2>Products by <?php echo htmlspecialchars($seller['name']); ?></h2>
            
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <a href="productdetails.php?id=<?php echo $product['id']; ?>" class="product-card">
                            <div class="product-image">
                                <img src="../uploads/products/images/<?php echo !empty($product['image_path']) ? htmlspecialchars($product['image_path']) : 'default-product.jpg'; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-price">RM <?php echo number_format($product['price'], 2); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="browse.php?seller=<?php echo $seller_id; ?>" class="view-all">
                    View all products by this seller <i class="fas fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <p>This seller hasn't uploaded any products yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // You can add interactive elements here
            console.log('Buyer profile page loaded');
        });
    </script>
</body>
</html>