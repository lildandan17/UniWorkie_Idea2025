<?php
session_start();
require_once __DIR__ . '/../config.php';
$error = '';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get seller's products count
$products_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($products_count);
$stmt->fetch();
$stmt->close();

// Get total saved count for seller's products
$total_saved = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM saved_items si
    JOIN products p ON si.product_id = p.id
    WHERE p.seller_id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($total_saved);
$stmt->fetch();
$stmt->close();

// Get recent products (last 5)
$recent_products = [];
$stmt = $conn->prepare("SELECT id, title, price, created_at FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_products[] = $row;
}
$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard | UniWorkie</title>
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
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease;
        }
        
        .card {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .card h3 {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 15px;
        }
        
        .card .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        
        .card .value i {
            margin-right: 15px;
            font-size: 2rem;
            color: var(--primary-light);
        }
        
        .card p {
            color: var(--text-light);
            font-size: 0.95rem;
            margin: 0;
        }
        
        /* Saved Products Card */
        .card.saved-card .value {
            color: var(--success);
        }
        
        .card.saved-card::before {
            background: linear-gradient(90deg, var(--success), #66BB6A);
        }
        
        .card.saved-card .value i {
            color: #A5D6A7;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
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
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px var(--shadow-color);
        }
        
        .btn i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        /* Recent Products */
        .recent-products {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease;
        }
        
        .recent-products:hover {
            transform: translateY(-5px);
        }
        
        .recent-products h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--primary-lighter);
        }
        
        .product-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .product-table th {
            text-align: left;
            padding: 15px;
            color: var(--text-light);
            font-weight: 600;
            background: var(--primary-lighter);
            border-bottom: 2px solid var(--primary-light);
        }
        
        .product-table td {
            padding: 15px;
            border-bottom: 1px solid var(--primary-lighter);
        }
        
        .status {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status.active {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
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
        }
        
        .empty-state .btn {
            margin: 0 auto;
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
            .dashboard-cards {
                grid-template-columns: 1fr 1fr;
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
            
            .dashboard-cards {
                grid-template-columns: 1fr;
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
            
            .quick-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">UniWorkie</div>
        <ul class="nav-menu">
            <li><a href="#" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box-open"></i> My Products</a></li>
            <li><a href="upload.php"><i class="fas fa-upload"></i> Upload Product</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1 class="page-title">Seller Dashboard</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>" alt="Profile">
                <span><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Seller'; ?></span>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Products</h3>
                <div class="value">
                    <i class="fas fa-boxes"></i>
                    <?php echo $products_count; ?>
                </div>
                <p><?php echo $products_count == 1 ? '1 product listed' : $products_count . ' products listed'; ?></p>
            </div>
                
            <div class="card saved-card">
                <h3>Saved by Buyers</h3>
                <div class="value">
                    <i class="fas fa-heart"></i>
                    <?php echo $total_saved; ?>
                </div>
                <p><?php echo $total_saved == 1 ? '1 save from buyers' : $total_saved . ' saves from buyers'; ?></p>
            </div>
        </div>

        <div class="quick-actions">
            <a href="upload.php" class="btn">
                <i class="fas fa-plus"></i> Add Product
            </a>
         
        </div>

        <div class="recent-products">
            <h2>Recent Products</h2>
            <?php if (empty($recent_products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>You haven't uploaded any products yet</p>
                    <a href="upload.php" class="btn">
                        <i class="fas fa-plus"></i> Upload Your First Product
                    </a>
                </div>
            <?php else: ?>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Date Added</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['title']); ?></td>
                                <td>RM <?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                <td><span class="status active">Active</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add interactive elements here if needed
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Seller dashboard loaded');
        });
    </script>
</body>
</html>