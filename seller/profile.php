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

// Initialize variables
$error = '';
$success = '';
$campus = '';
$diploma = '';
$semester = '';
$cgpa = '';
$bio = '';

// Fetch seller profile data
$stmt = $conn->prepare("SELECT `user_id`, `campus`, `diploma`, `semester`, `cgpa`, `bio` FROM `seller_profiles` WHERE user_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    $campus = $profile['campus'];
    $diploma = $profile['diploma'];
    $semester = $profile['semester'];
    $cgpa = $profile['cgpa'];
    $bio = $profile['bio'];
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campus = trim($_POST['campus']);
    $diploma = trim($_POST['diploma']);
    $semester = trim($_POST['semester']);
    $cgpa = trim($_POST['cgpa']);
    $bio = trim($_POST['bio']);

    // Validate inputs
    if (empty($campus) || empty($diploma) || empty($semester)) {
        $error = "Please fill in all required fields";
    } elseif (!is_numeric($cgpa) || $cgpa < 0 || $cgpa > 4.0) {
        $error = "Please enter a valid CGPA (0.0 - 4.0)";
    } else {
        // Check if profile exists
        $stmt = $conn->prepare("SELECT user_id FROM seller_profiles WHERE user_id = ?");
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            // Update existing profile
            $stmt = $conn->prepare("UPDATE seller_profiles SET campus = ?, diploma = ?, semester = ?, cgpa = ?, bio = ? WHERE user_id = ?");
            $stmt->bind_param("sssdsi", $campus, $diploma, $semester, $cgpa, $bio, $seller_id);
        } else {
            // Insert new profile
            $stmt = $conn->prepare("INSERT INTO seller_profiles (user_id, campus, diploma, semester, cgpa, bio) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssds", $seller_id, $campus, $diploma, $semester, $cgpa, $bio);
        }

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch user data including profile picture
$stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get products count for the seller
$products_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($products_count);
$stmt->fetch();
$stmt->close();

$conn->close();

// Campus options from your specification
$campus_options = ['Batu Pahat', 'Bangi', 'Ipoh', 'Kuantan', 'Alor Setar', 'Kota Bharu'];

// Diploma options from your specification
$diploma_options = [
    'Diploma Perakaunan',
    'Diploma Pengurusan Perniagaan',
    'Diploma Pengurusan Sumber Manusia',
    'Diploma Pengurusan Pelancongan',
    'Diploma Pendidikan Awal Kanak-Kanak',
    'Diploma Kejururawatan',
    'Diploma TESL',
    'Diploma Perbankan dan Kewangan Islam',
    'Diploma Teknologi Agro',
    'Diploma Multimedia',
    'Diploma Pengurusan dengan Multimedia',
    'Diploma Teknologi Maklumat',
    'Diploma Pengurusan Sukan',
    'Diploma Sains Komputer',
    'Diploma Pengurusan Pejabat'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile | UniWorkie</title>
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
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            height: 100vh;
            position: fixed;
            box-shadow: 4px 0 15px var(--shadow-color);
            padding-top: 20px;
        }
        
        .logo {
            color: var(--white);
            font-size: 1.75rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
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
        }
        
        .nav-menu li a:hover {
            background: rgba(255,255,255,0.15);
            border-left-color: var(--white);
        }
        
        .nav-menu li a.active {
            background: rgba(255,255,255,0.2);
            border-left-color: var(--white);
        }
        
        .nav-menu li a i {
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
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
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            border: 2px solid var(--primary-light);
        }
        
        /* Profile Container */
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        /* Profile Card */
        .profile-card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            text-align: center;
        }
        
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary-lighter);
            margin: 0 auto 20px;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .profile-email {
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 25px 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        /* Profile Form */
        .profile-form {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .form-title {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--primary-lighter);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-lighter);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px var(--shadow-color);
        }
        
        .btn i {
            margin-right: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .alert-error {
            background-color: rgba(255, 107, 107, 0.1);
            color: var(--error);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .profile-container {
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
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 15px;
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
            <li><a href="products.php"><i class="fas fa-box-open"></i> My Products</a></li>
            <li><a href="upload.php"><i class="fas fa-upload"></i> Upload Product</a></li>
            <li><a href="#" class="active"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1 class="page-title">Seller Profile</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" alt="Profile">
                <span><?php echo htmlspecialchars($user['name']); ?></span>
            </div>
        </div>

        <div class="profile-container">
            <!-- Profile Info Card -->
            <div class="profile-card">
                <img src="../uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" alt="Profile Picture" class="profile-pic">
                <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $products_count; ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                </div>
                
                
            </div>
            
            <!-- Profile Form -->
            <div class="profile-form">
                <h2 class="form-title">Edit Profile Information</h2>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="campus" class="form-label">Campus</label>
                        <select id="campus" name="campus" class="form-control" required>
                            <option value="">Select Campus</option>
                            <?php foreach ($campus_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $campus === $option ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="diploma" class="form-label">Diploma Program</label>
                        <select id="diploma" name="diploma" class="form-control" required>
                            <option value="">Select Diploma</option>
                            <?php foreach ($diploma_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $diploma === $option ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semester" class="form-label">Current Semester</label>
                        <select id="semester" name="semester" class="form-control" required>
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cgpa" class="form-label">Current CGPA</label>
                        <input type="number" id="cgpa" name="cgpa" class="form-control" step="0.01" min="0" max="4.0" 
                               value="<?php echo htmlspecialchars($cgpa); ?>" placeholder="e.g. 3.75">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">About Me</label>
                        <textarea id="bio" name="bio" class="form-control" placeholder="Tell buyers about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Change profile photo functionality
            const changePhotoBtn = document.getElementById('changePhotoBtn');
            
            if (changePhotoBtn) {
                changePhotoBtn.addEventListener('click', function() {
                    // In a real implementation, this would open a file dialog
                    alert('Photo upload functionality would be implemented here');
                });
            }
            
            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const cgpaInput = document.getElementById('cgpa');
                    if (cgpaInput.value && (cgpaInput.value < 0 || cgpaInput.value > 4.0)) {
                        alert('Please enter a valid CGPA between 0.0 and 4.0');
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>