<?php
session_start();
require_once __DIR__ . '/../config.php';

// Only proceed if valid buyer and seller_id provided
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer' || !isset($_GET['seller_id'])) {
    exit();
}

$buyer_id = $_SESSION['user_id'];
$seller_id = (int)$_GET['seller_id'];

$conn = getDBConnection();

// Check if this buyer already viewed this seller today
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM seller_profile_views 
    WHERE seller_id = ? AND buyer_id = ? AND DATE(viewed_at) = CURDATE()
");
$stmt->bind_param("ii", $seller_id, $buyer_id);
$stmt->execute();
$stmt->bind_result($already_viewed);
$stmt->fetch();
$stmt->close();

if (!$already_viewed) {
    // Only record if not viewed today
    $stmt = $conn->prepare("INSERT INTO seller_profile_views (seller_id, buyer_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $seller_id, $buyer_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>