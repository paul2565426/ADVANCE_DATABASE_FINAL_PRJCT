<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$fullName      = trim((string)($_POST['full_name']      ?? ''));
$email         = trim((string)($_POST['email']          ?? ''));
$contactNumber = trim((string)($_POST['contact_number'] ?? ''));
$title         = trim((string)($_POST['title']          ?? ''));
$description   = trim((string)($_POST['description']    ?? ''));
$categoryName  = trim((string)($_POST['category']       ?? ''));

// Validate required fields
if ($fullName === '' || $email === '' || $title === '' || $description === '' || $categoryName === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit();
}

// Get or create user
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if ($user) {
    $userId = (int)$user['user_id'];
} else {
    $ins = $conn->prepare(
        "INSERT INTO users (full_name, email, contact_number) VALUES (?, ?, ?)"
    );
    $contactVal = $contactNumber !== '' ? $contactNumber : null;
    $ins->bind_param('sss', $fullName, $email, $contactVal);
    if (!$ins->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save user info.']);
        exit();
    }
    $userId = (int)$conn->insert_id;
    $ins->close();
}

// Look up category
$stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ? LIMIT 1");
$stmt->bind_param('s', $categoryName);
$stmt->execute();
$res      = $stmt->get_result();
$category = $res->fetch_assoc();
$stmt->close();

if (!$category) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid category selected.']);
    exit();
}
$categoryId = (int)$category['category_id'];

// Insert prayer request
$stmt = $conn->prepare(
    "INSERT INTO prayer_requests (user_id, category_id, title, description) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('iiss', $userId, $categoryId, $title, $description);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Your prayer request has been submitted. God bless you!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit prayer request. Please try again.']);
}
$stmt->close();
