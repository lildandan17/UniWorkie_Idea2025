<?php
session_start();
require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if this is an AJAX request for filtered students
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $conn = getDBConnection();
    
    $where = [];
    $params = [];
    $types = '';
    
    // Build query based on filters
    if (isset($_GET['diploma'])) {
        $diploma = $_GET['diploma'];
        if ($diploma != 'any') {
            $where[] = "diploma = ?";
            $params[] = $diploma;
            $types .= 's';
        }
    }
    
    if (isset($_GET['campus'])) {
        $campus = $_GET['campus'];
        if ($campus != 'any') {
            $where[] = "campus = ?";
            $params[] = $campus;
            $types .= 's';
        }
    }
    
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
        if (!empty($search)) {
            $where[] = "(bio LIKE ?)";
            $params[] = "%$search%";
            $types .= 's';
        }
    }
    
    $query = "SELECT sp.user_id, u.name, u.email, u.phone, u.profile_pic, 
                     sp.campus, sp.diploma, sp.semester, sp.cgpa, sp.bio 
              FROM seller_profiles sp
              JOIN users u ON sp.user_id = u.id";
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    $query .= " ORDER BY sp.cgpa DESC LIMIT 12";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($students as $student) {
        echo renderStudentCard($student);
    }
    exit();
}

// Fetch initial students for page load
$conn = getDBConnection();
$students = [];

try {
    // Perbaikan: Tambahkan u.email dan u.phone dalam query
    $query = "SELECT sp.user_id, u.name, u.email, u.phone, u.profile_pic, 
                     sp.campus, sp.diploma, sp.semester, sp.cgpa, sp.bio 
              FROM seller_profiles sp
              JOIN users u ON sp.user_id = u.id
              ORDER BY sp.cgpa DESC LIMIT 12";
    $result = $conn->query($query);
    $students = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
$conn->close();

function renderStudentCard($student) {
    $profile_pic = !empty($student['profile_pic']) ? 
        '../uploads/' . $student['profile_pic'] : 
        '../images/default_profile.jpg';
    
    $whatsapp_link = '';
    $gmail_link = '';
    $profile_link = 'seller_profile.php?id='.$student['user_id'];
    
    // Debug: Tampilkan data phone dan email
    error_log("Phone: " . ($student['phone'] ?? 'NULL'));
    error_log("Email: " . ($student['email'] ?? 'NULL'));
    
    // Format WhatsApp link dengan nomor internasional
    if (!empty($student['phone'])) {
        $phone = $student['phone'];
        // Bersihkan nomor dari karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Hilangkan leading 0 jika ada
        $phone = ltrim($phone, '0');
        // Tambahkan kode negara Malaysia (+60) jika belum ada
        if (substr($phone, 0, 2) !== '60') {
            $phone = '60' . $phone;
        }
        $whatsapp_link = 'https://wa.me/' . $phone;
    }
    
    // Format email link
    if (!empty($student['email'])) {
        $gmail_link = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . $student['email']; // urlencode tidak diperlukan untuk mailto:
    }
    
    return '
    <div class="student-card">
        <div class="student-image" style="background-image: url(\''.$profile_pic.'\')"></div>
        <div class="student-info">
            <div class="student-name">'.htmlspecialchars($student['name']).'</div>
            <div class="student-details">
                <span><i class="fas fa-graduation-cap"></i> '.htmlspecialchars($student['diploma']).'</span>
                <span><i class="fas fa-map-marker-alt"></i> '.htmlspecialchars($student['campus']).'</span>
                <span><i class="fas fa-layer-group"></i> Semester '.htmlspecialchars($student['semester']).'</span>
                <span><i class="fas fa-star"></i> CGPA: '.htmlspecialchars($student['cgpa']).'</span>
            </div>
            <div class="student-bio">'.nl2br(htmlspecialchars($student['bio'])).'</div>
            <div class="contact-options">
                <a href="'.$profile_link.'" class="profile-btn">
                    <i class="fas fa-user"></i> View Profile
                </a>
                '.($whatsapp_link ? '
                <a href="'.$whatsapp_link.'" class="whatsapp-btn" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>' : '
                <button class="whatsapp-btn disabled" disabled>
                    <i class="fab fa-whatsapp"></i> No WhatsApp
                </button>').'
                '.($gmail_link ? '
                <a href="'.$gmail_link.'" class="gmail-btn" target="_blank">
                    <i class="fas fa-envelope"></i> Email
                </a>' : '
                <button class="gmail-btn disabled" disabled>
                    <i class="fas fa-envelope"></i> No Email
                </button>').'
            </div>
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Find And Recruit | UniWorkie</title>
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

        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 12px 20px;
            border: 2px solid var(--primary-lighter);
            border-radius: 25px;
            background: var(--white);
            color: var(--text-dark);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-lighter);
        }
        
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .student-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .student-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            background-color: var(--primary-lighter);
        }
        
        .student-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .student-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-dark);
        }
        
        .student-details {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .student-details span {
            background: var(--primary-lighter);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .student-details i {
            color: var(--primary);
        }
        
        .student-bio {
            margin-bottom: 15px;
            color: var(--text-light);
            line-height: 1.5;
            flex-grow: 1;
        }
        
        .contact-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: auto;
        }
        
        .contact-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .contact-btn i {
            font-size: 0.9rem;
        }

        .contact-options {
    display: flex;
    gap: 10px;
    margin-top: auto;
    flex-wrap: wrap;
}

.profile-btn, .whatsapp-btn, .gmail-btn {
    padding: 8px 12px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;
    flex-grow: 1;
    text-align: center;
}

.profile-btn {
    background: var(--primary);
    color: white;
    border: none;
}

.profile-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.whatsapp-btn {
    background: #25D366;
    color: white;
    border: none;
}

.whatsapp-btn:hover {
    background: #128C7E;
    transform: translateY(-2px);
}

.gmail-btn {
    background: #DB4437;
    color: white;
    border: none;
}

.gmail-btn:hover {
    background: #C1351A;
    transform: translateY(-2px);
}

.disabled {
    background: #cccccc !important;
    cursor: not-allowed;
    opacity: 0.7;
}

.disabled:hover {
    transform: none !important;
}
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">UniWorkie</div>
        <ul class="nav-menu">
           <li><a href="dashboard.php" ><i class="fas fa-home"></i> Home</a></li>
            <li><a href="save.php" ><i class="fas fa-heart"></i> Saved Items</a></li>
            <li><a href="recruit.php" class="active"><i class="fas fa-user-graduate"></i> Find And Recruit</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="welcome">Find Students</h1>
            <div class="user-profile">
                <img src="../uploads/<?php echo $_SESSION['profile_pic'] ?? 'default.jpg'; ?>" alt="Profile">
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-bar" placeholder="Search by skills or bio..." id="searchInput">
        </div>

        <!-- Filter Options -->
        <div class="filter-container">
            <select class="filter-select" id="diplomaFilter">
                <option value="any">Any Diploma</option>
                <option value="Diploma Perakaunan">Diploma Perakaunan</option>
                <option value="Diploma Pengurusan Perniagaan">Diploma Pengurusan Perniagaan</option>
                <option value="Diploma Pengurusan Sumber Manusia">Diploma Pengurusan Sumber Manusia</option>
                <option value="Diploma Pengurusan Pelancongan">Diploma Pengurusan Pelancongan</option>
                <option value="Diploma Pendidikan Awal Kanak-Kanak">Diploma Pendidikan Awal Kanak-Kanak</option>
                <option value="Diploma Kejururawatan">Diploma Kejururawatan</option>
                <option value="Diploma TESL">Diploma TESL</option>
                <option value="Diploma Perbankan dan Kewangan Islam">Diploma Perbankan dan Kewangan Islam</option>
                <option value="Diploma Teknologi Agro">Diploma Teknologi Agro</option>
                <option value="Diploma Multimedia">Diploma Multimedia</option>
                <option value="Diploma Pengurusan dengan Multimedia">Diploma Pengurusan dengan Multimedia</option>
                <option value="Diploma Teknologi Maklumat">Diploma Teknologi Maklumat</option>
                <option value="Diploma Pengurusan Sukan">Diploma Pengurusan Sukan</option>
                <option value="Diploma Sains Komputer">Diploma Sains Komputer</option>
                <option value="Diploma Pengurusan Pejabat">Diploma Pengurusan Pejabat</option>
            </select>
            
            <select class="filter-select" id="campusFilter">
                <option value="any">Any Campus</option>
                <option value="Batu Pahat">Batu Pahat</option>
                <option value="Bangi">Bangi</option>
                <option value="Ipoh">Ipoh</option>
                <option value="Kuantan">Kuantan</option>
                <option value="Alor Setar">Alor Setar</option>
                <option value="Kota Bharu">Kota Bharu</option>
            </select>
        </div>

        <!-- Students Grid -->
        <div class="student-grid" id="studentsGrid">
            <?php foreach ($students as $student): ?>
                <?php echo renderStudentCard($student); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Filter functionality
        const diplomaFilter = document.getElementById('diplomaFilter');
        const campusFilter = document.getElementById('campusFilter');
        const searchInput = document.getElementById('searchInput');
        
        // Debounce function to limit how often we make requests
        function debounce(func, timeout = 300) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }
        
        // Function to fetch filtered students
        function fetchFilteredStudents() {
            const diploma = diplomaFilter.value;
            const campus = campusFilter.value;
            const search = searchInput.value.trim();
            
            const studentsGrid = document.getElementById('studentsGrid');
            studentsGrid.innerHTML = '<div class="loading">Finding students...</div>';
            
            let url = `recruit.php?ajax=1&diploma=${encodeURIComponent(diploma)}&campus=${encodeURIComponent(campus)}`;
            if (search.length > 0) {
                url += `&search=${encodeURIComponent(search)}`;
            }
            
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    studentsGrid.innerHTML = html;
                })
                .catch(error => {
                    studentsGrid.innerHTML = '<div class="loading">Error loading students. Please try again.</div>';
                    console.error('Error:', error);
                });
        }
        
        // Event listeners for filters
        diplomaFilter.addEventListener('change', fetchFilteredStudents);
        campusFilter.addEventListener('change', fetchFilteredStudents);
        searchInput.addEventListener('input', debounce(fetchFilteredStudents));
        
        // Function to contact student
        function contactStudent(userId) {
            // This would typically open a modal or redirect to a messaging page
            alert(`Contacting student with ID: ${userId}\nThis would open a messaging interface in a real application.`);
        }
    </script>
</body>
</html>