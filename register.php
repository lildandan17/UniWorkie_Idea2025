<?php
session_start();
require_once 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getDBConnection();
    
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // Validate seller email
    if ($role == 'seller' && !preg_match('/@.*\.edu\.my$/i', $email)) {
        $error = "Sellers must use valid student email";
    } else {
        // Handle file upload
        $profile_pic = 'default.jpg';
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid().'.'.$ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/'.$profile_pic);
        }
        
        $sql = "INSERT INTO users (name, email, phone, password, profile_pic, role) 
                VALUES ('$name', '$email', '$phone', '$password', '$profile_pic', '$role')";
        
        if ($conn->query($sql)) {
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $_SESSION['name'] = $name;
            $_SESSION['profile_pic'] = $profile_pic;
            
            header('Location: '.($role == 'seller' ? 'seller/complete_profile.php' : 'buyer/dashboard.php'));
            exit();
        } else {
            $error = "Email already registered! Error: ".$conn->error;
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>UniWorkie - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --primary-light: #61B0FE;
            --primary-lighter: #EDF5FF;
            --primary-dark: #357ABD;
            --accent: #2196F3;
            --white: #ffffff;
            --text-dark: #2C3E50;
            --text-light: #546E7A;
            --error: #EF5350;
            --shadow-color: rgba(74, 144, 226, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('background1.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,255,255,0.85));
            z-index: 0;
        }

        .register-container {
            background: var(--white);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px var(--shadow-color);
            width: 450px;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease;
        }

        .logo {
            color: var(--primary);
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: color 0.3s ease;
        }

        input, select {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid var(--primary-lighter);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--text-dark);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--shadow-color);
            transform: translateY(-2px);
        }

        input:focus + i {
            color: var(--primary);
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%234A90E2' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        .file-upload {
            margin: 20px 0;
            text-align: left;
        }

        .file-upload label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .file-upload input[type="file"] {
            padding: 10px;
            font-size: 0.9rem;
        }

        button {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        button:hover::before {
            opacity: 1;
        }

        button span {
            position: relative;
            z-index: 1;
        }

        .error {
            color: var(--error);
            margin: 15px 0;
            padding: 10px;
            border-radius: 8px;
            background: rgba(239, 83, 80, 0.1);
            font-size: 0.9rem;
            animation: shake 0.5s ease;
        }

        .login-link {
            margin-top: 25px;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 480px) {
            .register-container {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            UniWorkie
        </div>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <input type="text" name="name" placeholder="Full Name" required>
                <i class="fas fa-user"></i>
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
                <i class="fas fa-envelope"></i>
            </div>
            <div class="input-group">
                <input type="tel" name="phone" placeholder="Phone Number" required>
                <i class="fas fa-phone"></i>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-lock"></i>
            </div>
            
            <div class="file-upload">
                <label>Profile Picture:</label>
                <input type="file" name="profile_pic" accept="image/*">
            </div>
            
            <div class="input-group">
                <select name="role" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>
                <i class="fas fa-user-tag"></i>
            </div>
            
            <button type="submit"><span>Create Account</span></button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
