<?php
session_start();
$_SESSION = array();
session_destroy();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// HTML with SweetAlert for the logout message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out</title>
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background-color:rgb(157, 242, 255);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .swal2-popup {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .swal2-title {
            color:rgb(143, 185, 226);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Logged Out Successfully!',
                text: 'You have been securely logged out of your account.',
                icon: 'success',
                confirmButtonColor: '#4e73df',
                confirmButtonText: 'Return to Login',
                backdrop: `
                    rgba(78, 115, 223, 0.4)
                    url("https://i.pinimg.com/originals/8b/80/29/8b8029f3021a1535b5b26b3a9b7e8e0b.gif")
                    center top
                    no-repeat
                `,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../login.php";
                } else {
                    // Auto-redirect after 3 seconds if they don't click
                    setTimeout(function() {
                        window.location.href = "../login.php";
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html>