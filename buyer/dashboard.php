<?php
session_start();
require_once '../config.php';

// Redirect if not buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header('Location: ../login.php');
    exit();
}

// Check if this is an AJAX request for filtered products
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $conn = getDBConnection();
    
    if (isset($_GET['category'])) {
        // Category filter
        $category = $conn->real_escape_string($_GET['category']);
        if ($category == 'all') {
            $query = "SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id ORDER BY RAND() LIMIT 12";
        } else {
            $query = "SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.category = '$category' ORDER BY RAND() LIMIT 12";
        }
    } elseif (isset($_GET['search'])) {
        // Search filter
        $search = $conn->real_escape_string($_GET['search']);
        $query = "SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id 
                 WHERE p.title LIKE '%$search%' OR p.description LIKE '%$search%' 
                 ORDER BY p.title LIMIT 12";
    }
    
    $result = $conn->query($query);
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($products as $product) {
        echo renderProductCard($product);
    }
    exit();
}

// Fetch products from database for initial load
$conn = getDBConnection();
$recommended_products = [];

try {
    // Recommended products (random 6 products)
    $rec_query = "SELECT p.*, u.name as seller_name 
                 FROM products p
                 JOIN users u ON p.seller_id = u.id
                 ORDER BY RAND() LIMIT 6";
    $rec_result = $conn->query($rec_query);
    $recommended_products = $rec_result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
$conn->close();

function renderProductCard($product) {
    $image_url = !empty($product['image_path']) ? 
        '../uploads/products/images/' . basename($product['image_path']) : 
        '../images/default_product.jpg';
    
    return '
    <div class="product-card" onclick="window.location.href=\'productdetails.php?id='.$product['id'].'\'">
        <div class="product-image" style="background-image: url(\''.$image_url.'\')"></div>
        <div class="product-info">
            <div class="product-title" title="'.htmlspecialchars($product['title']).'">
                '.htmlspecialchars($product['title']).'
            </div>
            <div class="product-seller">By '.htmlspecialchars($product['seller_name']).'</div>
        </div>
    </div>';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buyer Dashboard | UniWorkie</title>
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
            <h1 class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo $_SESSION['profile_pic'] ?? 'default.jpg'; ?>" alt="Profile">
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-bar" placeholder="Search for products..." id="searchInput">
        </div>

        <!-- Category Filter -->
        <div class="category-filter">
            <button class="category-btn active" data-category="all">All</button>
            <button class="category-btn" data-category="Art & Design">Art & Design</button>
            <button class="category-btn" data-category="Information Technology">Information Technology</button>
            <button class="category-btn" data-category="Business & Finance">Business & Finance</button>
            <button class="category-btn" data-category="Education">Education</button>
            <button class="category-btn" data-category="Health & Medicine">Health & Medicine</button>
            <button class="category-btn" data-category="Tourism & Services">Tourism & Services</button>
            <button class="category-btn" data-category="Social Sciences & Psychology">Social Sciences & Psychology</button>
            <button class="category-btn" data-category="Engineering">Engineering</button>
            <button class="category-btn" data-category="Law">Law</button>
        </div>

        <!-- Recommended Products -->
        <h2 class="section-title">Recommended For You</h2>
        <div class="product-grid" id="recommendedProducts">
            <?php foreach ($recommended_products as $product): ?>
                <?php echo renderProductCard($product); ?>
            <?php endforeach; ?>
        </div>

    </div>

      <script>
    // Search functionality - prevent Enter key submission
    document.getElementById('searchInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission/page reload
        }
    });

    // Live search functionality
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.trim();
        
        // Clear previous timeout if it exists
        clearTimeout(searchTimeout);
        
        // Only search if there are at least 2 characters or empty (to show all)
        if (searchTerm.length >= 2 || searchTerm.length === 0) {
            // Set a small delay to prevent too many requests while typing
            searchTimeout = setTimeout(() => {
                // Show loading state
                const recommendedGrid = document.getElementById('recommendedProducts');
                recommendedGrid.innerHTML = '<div class="loading">Searching products...</div>';
                
                // Fetch search results
                fetch(`dashboard.php?ajax=1&search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.text())
                    .then(html => {
                        recommendedGrid.innerHTML = html;
                    })
                    .catch(error => {
                        recommendedGrid.innerHTML = '<div class="loading">Error searching products. Please try again.</div>';
                        console.error('Error:', error);
                    });
            }, 300); // 300ms delay after typing stops
        }
    });

    // Update your existing category filter to reset search input
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Reset search input
            document.getElementById('searchInput').value = '';
            
            // Rest of your existing category filter code...
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            const recommendedGrid = document.getElementById('recommendedProducts');
            recommendedGrid.innerHTML = '<div class="loading">Loading products...</div>';
            
            fetchFilteredProducts(category, 'recommendedProducts');
        });
    });

    function fetchFilteredProducts(category, targetElementId) {
        fetch(`dashboard.php?ajax=1&category=${encodeURIComponent(category)}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById(targetElementId).innerHTML = html;
            })
            .catch(error => {
                document.getElementById(targetElementId).innerHTML = 
                    '<div class="loading">Error loading products. Please try again.</div>';
                console.error('Error:', error);
            });
    }
</script>
</body>
</html>