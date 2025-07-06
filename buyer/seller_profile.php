<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: recruit.php');
    exit();
}

$seller_id = intval($_GET['id']);
$conn = getDBConnection();

// Get seller profile
$stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.phone, u.profile_pic, 
                        sp.campus, sp.diploma, sp.semester, sp.cgpa, sp.bio
                        FROM users u
                        LEFT JOIN seller_profiles sp ON u.id = sp.user_id
                        WHERE u.id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: recruit.php');
    exit();
}

$seller = $result->fetch_assoc();

// Get seller's products
$products = [];
$products_stmt = $conn->prepare("SELECT id, title, description, category, price, image_path 
                                FROM products 
                                WHERE seller_id = ? 
                                ORDER BY created_at DESC");
$products_stmt->bind_param("i", $seller_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();
$products_stmt->close();
$conn->close();

// Prepare contact links
$whatsapp_link = !empty($seller['phone']) ? 'https://wa.me/6'.$seller['phone'] : null;
$gmail_link = !empty($seller['email']) ? 'mailto:'.$seller['email'] : null;

function renderProductCard($product) {
    $image_url = !empty($product['image_path']) ? 
        '../uploads/products/images/' . basename($product['image_path']) : 
        '../images/default_product.jpg';
    
    return '
    <div class="product-card" onclick="window.location=\'productdetails.php?id='.$product['id'].'\'">
        <div class="product-image" style="background-image: url(\''.$image_url.'\')"></div>
        <div class="product-info">
            <div class="product-title">'.htmlspecialchars($product['title']).'</div>
            <div class="product-category">'.htmlspecialchars($product['category']).'</div>
            <div class="product-price">RM '.number_format($product['price'], 2).'</div>
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($seller['name']); ?>'s Profile | UniWorkie</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* [Keep all your existing CSS variables and styles] */
        
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
        
        .welcome {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: bold;
            animation: fadeInLeft 0.5s ease;
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
        
        .search-container {
            position: relative;
            margin-bottom: 30px;
            width: 100%;
            max-width: 600px;
            animation: fadeInUp 0.5s ease;
        }
        
        .search-bar {
            width: 100%;
            padding: 15px 25px 15px 50px;
            border: 2px solid var(--primary-lighter);
            border-radius: 30px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--text-dark);
        }
        
        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-lighter);
            transform: translateY(-2px);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            transition: color 0.3s ease;
        }
        
        .search-bar:focus + .search-icon {
            color: var(--primary-dark);
        }
        
        .category-filter {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding: 10px 0;
            animation: slideIn 0.5s ease;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--primary-lighter);
        }
        
        .category-btn {
            background: var(--white);
            border: 2px solid var(--primary-lighter);
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .category-btn:hover {
            background: var(--primary-lighter);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .category-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }
        
        .section-title {
            color: var(--primary);
            font-size: 1.75rem;
            margin: 40px 0 20px 0;
            font-weight: bold;
            animation: fadeInLeft 0.5s ease;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
            animation: fadeIn 0.5s ease;
        }
        
        .product-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: all 0.3s ease;
            cursor: pointer;
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
        
        .product-seller {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        .loading {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: var(--primary);
            font-size: 1.1rem;
            animation: pulse 1.5s infinite;
        }
        
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
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
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

        .profile-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-sidebar {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px var(--shadow-color);
            align-self: start;
            position: sticky;
            top: 30px;
        }
        
        .profile-content {
            flex-grow: 1;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-pic-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary);
            margin: 0 auto 15px;
        }
        
        .profile-name {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .profile-diploma {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .profile-campus {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .profile-bio {
            background: var(--primary-lighter);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .contact-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        
        .contact-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .whatsapp-btn {
            background: #25D366;
        }
        
        .gmail-btn {
            background: #DB4437;
        }
        
        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .products-section {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            margin-top: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            color: var(--primary);
            font-size: 1.5rem;
            margin: 0;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid var(--primary-lighter);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 150px;
            background-size: cover;
            background-position: center;
            background-color: var(--primary-lighter);
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-dark);
        }
        
        .product-category {
            font-size: 0.8rem;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .product-price {
            font-weight: bold;
            color: var(--text-dark);
        }
        
        .academic-details {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px var(--shadow-color);
            margin-bottom: 30px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-item {
            background: var(--primary-lighter);
            padding: 12px;
            border-radius: 8px;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--text-dark);
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
        
        <div class="profile-layout">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-header">
                    <img src="../uploads/<?php echo htmlspecialchars($seller['profile_pic'] ?? 'default.jpg'); ?>" 
                         alt="Profile Picture" class="profile-pic-large">
                    <h2 class="profile-name"><?php echo htmlspecialchars($seller['name']); ?></h2>
                    <div class="profile-diploma"><?php echo htmlspecialchars($seller['diploma'] ?? 'Not specified'); ?></div>
                    <div class="profile-campus"><?php echo htmlspecialchars($seller['campus'] ?? 'Not specified'); ?> Campus</div>
                </div>
                
                <div class="profile-bio">
                    <h3 class="section-title">About</h3>
                    <p><?php echo nl2br(htmlspecialchars($seller['bio'] ?? 'No bio available')); ?></p>
                </div>
                
                <div class="contact-options">
                    <?php if ($whatsapp_link): ?>
                    <a href="<?php echo $whatsapp_link; ?>" class="contact-btn whatsapp-btn" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($gmail_link): ?>
                    <a href="<?php echo $gmail_link; ?>" class="contact-btn gmail-btn" target="_blank">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Profile Content -->
            <div class="profile-content">
                <!-- Academic Details -->
                <div class="academic-details">
                    <h2 class="section-title">Academic Details</h2>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Campus</div>
                            <div class="detail-value"><?php echo htmlspecialchars($seller['campus'] ?? 'Not specified'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Diploma</div>
                            <div class="detail-value"><?php echo htmlspecialchars($seller['diploma'] ?? 'Not specified'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Semester</div>
                            <div class="detail-value"><?php echo htmlspecialchars($seller['semester'] ?? 'Not specified'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">CGPA</div>
                            <div class="detail-value"><?php echo htmlspecialchars($seller['cgpa'] ?? 'Not specified'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Products Section -->
                <div class="products-section">
                    <div class="section-header">
                        <h2 class="section-title">Products Offered</h2>
                    </div>
                    
                    <?php if (!empty($products)): ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                                <?php echo renderProductCard($product); ?>
                            <?php endforeach; ?>
                        </div>
                        <!-- Please remove this  -->
                    <?php else: ?>
                        <p>This seller hasn't listed any products yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>