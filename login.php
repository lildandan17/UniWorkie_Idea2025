<?php
session_start();
require_once 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getDBConnection();
    
    // Prepare and bind parameters to prevent SQL injection
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND is_banned = FALSE");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            
            // Update last online time
            $update_stmt = $conn->prepare("UPDATE users SET last_online=NOW() WHERE id=?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            // Redirect based on role
            $redirect = match($user['role']) {
                'admin' => 'admin/dashboard.php',
                'seller' => 'seller/dashboard.php',
                default => 'buyer/dashboard.php'
            };
            header("Location: $redirect");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Email not registered or account banned!";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>UniWorkie - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --primary-light: #61B0FE;
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
            overflow: hidden;
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

        .login-container {
            background: var(--white);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px var(--shadow-color);
            width: 400px;
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

        input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #E3F2FD;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--text-dark);
            background: #F8FBFF;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--shadow-color);
            transform: translateY(-2px);
        }

        input:focus + i {
            color: var(--primary);
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
            margin-top: 20px;
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

        .register-link {
            margin-top: 25px;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
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
            .login-container {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            UniWorkie
        </div>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
                <i class="fas fa-envelope"></i>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-lock"></i>
            </div>
            <button type="submit"><span>Login</span></button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
