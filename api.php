<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

function json_ok($data = null): void {
    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit();
}

function json_err(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

function require_admin(): int {
    if (!isset($_SESSION['admin_id'])) {
        json_err('Unauthorized', 401);
    }
    return (int)$_SESSION['admin_id'];
}

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

// -------------------------
// Requests (CRUD-lite)
// -------------------------
if ($action === 'list_requests') {
    require_admin();
    $sql = "SELECT
                pr.request_id AS id,
                pr.title,
                pr.description,
                c.category_name AS category,
                pr.status,
                DATE_FORMAT(pr.submitted_at, '%b %e, %Y') AS created
            FROM prayer_requests pr
            JOIN categories c ON pr.category_id = c.category_id
            ORDER BY pr.submitted_at DESC";
    $res = $conn->query($sql);
    if (!$res) json_err('Failed to fetch requests', 500);
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    json_ok($rows);
}

if ($action === 'update_status') {
    $admin_id = require_admin();
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
    $allowed = ['Pending', 'In Progress', 'Answered'];
    if ($id <= 0) json_err('Missing id');
    if (!in_array($status, $allowed, true)) json_err('Invalid status');

    $stmt = $conn->prepare("UPDATE prayer_requests SET status = ? WHERE request_id = ?");
    $stmt->bind_param('si', $status, $id);
    if (!$stmt->execute()) json_err('Failed to update status', 500);

    // Log admin action (matches your admin_logs schema)
    $remarks = "Updated status to {$status}";
    $logStmt = $conn->prepare("INSERT INTO admin_logs (admin_id, request_id, action, remarks, action_date) VALUES (?, ?, ?, ?, NOW())");
    $actionName = 'Update Status';
    $logStmt->bind_param('iiss', $admin_id, $id, $actionName, $remarks);
    $logStmt->execute();

    json_ok(['id' => $id, 'status' => $status]);
}

if ($action === 'delete_request') {
    $admin_id = require_admin();
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) json_err('Missing id');
    // Log before delete so FK to prayer_requests is still valid at insert time
    $remarks = "Deleted request #{$id}";
    $logStmt = $conn->prepare("INSERT INTO admin_logs (admin_id, request_id, action, remarks, action_date) VALUES (?, ?, ?, ?, NOW())");
    $actionName = 'Delete Request';
    $logStmt->bind_param('iiss', $admin_id, $id, $actionName, $remarks);
    $logStmt->execute();

    $stmt = $conn->prepare("DELETE FROM prayer_requests WHERE request_id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) json_err('Failed to delete request', 500);
    json_ok(['id' => $id]);
}

// -------------------------
// Dashboard / Reports stats
// -------------------------
if ($action === 'get_status_counts') {
    require_admin();
    $sql = "SELECT status, COUNT(*) AS count FROM prayer_requests GROUP BY status";
    $res = $conn->query($sql);
    if (!$res) json_err('Failed to fetch status counts', 500);
    $map = ['Pending' => 0, 'In Progress' => 0, 'Answered' => 0];
    while ($r = $res->fetch_assoc()) {
        $status = (string)$r['status'];
        if (array_key_exists($status, $map)) $map[$status] = (int)$r['count'];
    }
    $map['Total'] = $map['Pending'] + $map['In Progress'] + $map['Answered'];
    json_ok($map);
}

if ($action === 'get_category_counts') {
    require_admin();
    $sql = "SELECT c.category_name AS category, COUNT(*) AS count
            FROM prayer_requests pr
            JOIN categories c ON pr.category_id = c.category_id
            GROUP BY c.category_id, c.category_name
            ORDER BY count DESC";
    $res = $conn->query($sql);
    if (!$res) json_err('Failed to fetch category counts', 500);
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    json_ok($rows);
}

if ($action === 'get_recent_requests') {
    require_admin();
    $limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 4;
    $stmt = $conn->prepare("SELECT title, status, DATE_FORMAT(submitted_at, '%b %e, %Y') AS created
                            FROM prayer_requests
                            ORDER BY submitted_at DESC
                            LIMIT ?");
    $stmt->bind_param('i', $limit);
    if (!$stmt->execute()) json_err('Failed to fetch recent requests', 500);
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    json_ok($rows);
}

if ($action === 'get_daily_stats') {
    require_admin();
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
    if ($days <= 0) $days = 7;

    $query = "SELECT DATE(submitted_at) as date, COUNT(*) as count
              FROM prayer_requests
              WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY DATE(submitted_at)
              ORDER BY date";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $days);
    if (!$stmt->execute()) json_err('Failed to fetch daily stats', 500);
    $result = $stmt->get_result();

    $stats = [];
    while ($row = $result->fetch_assoc()) $stats[] = $row;
    json_ok($stats);
}

// -------------------------
// Admin download logs (separate table; admin_logs requires request_id)
// -------------------------
if ($action === 'log_download') {
    $admin_id = require_admin();
    $download_type = isset($_POST['download_type']) ? trim((string)$_POST['download_type']) : '';
    if (!$download_type) json_err('Missing download_type');

    $remarks = $download_type;
    $stmt = $conn->prepare("INSERT INTO admin_download_logs (admin_id, action, remarks, action_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iss', $admin_id, $download_type, $remarks);
    if (!$stmt->execute()) json_err('Failed to log download', 500);
    json_ok(['message' => 'Download logged']);
}

if ($action === 'get_user_engagement') {
    require_admin();
    $sql = "SELECT 
                u.full_name,
                u.email,
                u.contact_number,
                COUNT(pr.request_id) AS total_requests_submitted,
                SUM(CASE WHEN pr.status = 'Answered' THEN 1 ELSE 0 END) AS answered_requests,
                (SELECT COUNT(*) 
                 FROM admin_logs al 
                 JOIN prayer_requests pr2 ON al.request_id = pr2.request_id 
                 WHERE pr2.user_id = u.user_id) AS admin_actions_count,
                (SELECT GROUP_CONCAT(DISTINCT c.category_name SEPARATOR ', ')
                 FROM prayer_requests pr3
                 JOIN categories c ON pr3.category_id = c.category_id
                 WHERE pr3.user_id = u.user_id 
                 LIMIT 3) AS recent_categories,
                (SELECT DATE_FORMAT(MAX(pr4.submitted_at), '%Y-%m')
                 FROM prayer_requests pr4 
                 WHERE pr4.user_id = u.user_id) AS latest_month,
                (SELECT COUNT(DISTINCT pr5.category_id)
                 FROM prayer_requests pr5 
                 WHERE pr5.user_id = u.user_id) AS unique_categories_count
            FROM users u
            LEFT JOIN prayer_requests pr ON u.user_id = pr.user_id
            GROUP BY u.user_id, u.full_name, u.email, u.contact_number
            HAVING total_requests_submitted > 0
            ORDER BY total_requests_submitted DESC, answered_requests DESC
            LIMIT 10";
    $res = $conn->query($sql);
    if (!$res) json_err('Failed to fetch user engagement data', 500);
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'full_name' => $r['full_name'],
            'email' => $r['email'],
            'contact_number' => $r['contact_number'] ?: '—',
            'total_requests_submitted' => (int)$r['total_requests_submitted'],
            'answered_requests' => (int)$r['answered_requests'],
            'admin_actions_count' => (int)$r['admin_actions_count'],
            'recent_categories' => $r['recent_categories'] ?: '—',
            'latest_month' => $r['latest_month'] ?: '—',
            'unique_categories_count' => (int)$r['unique_categories_count']
        ];
    }
    json_ok($rows);
}

if ($action === 'get_high_activity_users') {
    require_admin();
    $sql = "SELECT 
                a.first_name AS admin_first_name,
                a.last_name AS admin_last_name,
                al.action AS admin_action,
                pr.title AS request_title,
                c.category_name,
                u.full_name AS requester_name
            FROM admin_logs al
            JOIN admins a ON al.admin_id = a.admin_id
            JOIN prayer_requests pr ON al.request_id = pr.request_id
            JOIN categories c ON pr.category_id = c.category_id
            JOIN users u ON pr.user_id = u.user_id
            WHERE u.user_id IN (
                SELECT user_id 
                FROM prayer_requests 
                GROUP BY user_id 
                HAVING COUNT(request_id) > (
                    SELECT AVG(req_count) 
                    FROM (
                        SELECT COUNT(request_id) AS req_count 
                        FROM prayer_requests 
                        GROUP BY user_id
                    ) AS UserRequestCounts
                )
            )
            ORDER BY al.action_date DESC
            LIMIT 20";
    $res = $conn->query($sql);
    if (!$res) json_err('Failed to fetch high activity users data', 500);
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'admin_first_name' => $r['admin_first_name'],
            'admin_last_name' => $r['admin_last_name'],
            'admin_action' => $r['admin_action'],
            'request_title' => $r['request_title'],
            'category_name' => $r['category_name'],
            'requester_name' => $r['requester_name']
        ];
    }
    json_ok($rows);
}

if ($action === 'get_category_analytics') {
    require_admin();
    $sql = "WITH CategoryStats AS (
                SELECT 
                    category_id,
                    COUNT(request_id) AS total_requests,
                    MIN(submitted_at) AS earliest_request_date,
                    MAX(submitted_at) AS latest_request_date
                FROM prayer_requests
                GROUP BY category_id
            )
            SELECT 
                c.category_name,
                cs.total_requests,
                DATE(cs.earliest_request_date) AS earliest_request_date,
                DATE(cs.latest_request_date) AS latest_request_date,
                (SELECT u.full_name 
                 FROM prayer_requests pr
                 JOIN users u ON pr.user_id = u.user_id
                 WHERE pr.category_id = c.category_id
                 ORDER BY pr.submitted_at DESC 
                 LIMIT 1) AS latest_requester_name
            FROM categories c
            JOIN CategoryStats cs ON c.category_id = cs.category_id
            ORDER BY cs.total_requests DESC";
    $res = $conn->query($sql);
    if (!$res) json_err('Failed to fetch category analytics data', 500);
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'category_name' => $r['category_name'],
            'total_requests' => (int)$r['total_requests'],
            'earliest_request_date' => $r['earliest_request_date'],
            'latest_request_date' => $r['latest_request_date'],
            'latest_requester_name' => $r['latest_requester_name'] ?: '—'
        ];
    }
    json_ok($rows);
}

if ($action === 'get_monthly_request_computation') {
    require_admin();
    $sql = "WITH MonthlyRequestStats AS (
                SELECT 
                    DATE_FORMAT(submitted_at, '%Y-%m') AS report_month,
                    category_id,
                    COUNT(request_id) AS total_monthly_requests
                FROM prayer_requests
                GROUP BY DATE_FORMAT(submitted_at, '%Y-%m'), category_id
            )
            SELECT 
                mrs.report_month,
                c.category_name,
                mrs.total_monthly_requests,
                (SELECT COUNT(DISTINCT pr.user_id) 
                 FROM prayer_requests pr
                 WHERE DATE_FORMAT(pr.submitted_at, '%Y-%m') = mrs.report_month 
                   AND pr.category_id = mrs.category_id) AS unique_users_this_month
            FROM MonthlyRequestStats mrs
            JOIN categories c ON mrs.category_id = c.category_id
            ORDER BY mrs.report_month DESC, mrs.total_monthly_requests DESC
            LIMIT 50";
    $res = $conn->query($sql);
    if (!$res) json_err('Failed to fetch monthly request computation data', 500);
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'report_month' => $r['report_month'],
            'category_name' => $r['category_name'],
            'total_monthly_requests' => (int)$r['total_monthly_requests'],
            'unique_users_this_month' => (int)$r['unique_users_this_month']
        ];
    }
    json_ok($rows);
}

if ($action === 'get_downloads') {
    $admin_id = require_admin();
    $query = "SELECT admin_id, action, remarks, action_date
              FROM admin_download_logs
              WHERE admin_id = ? AND action IN ('Export Summary', 'Export to Excel', 'Export to CSV')
              ORDER BY action_date DESC
              LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $admin_id);
    if (!$stmt->execute()) json_err('Failed to fetch downloads', 500);
    $result = $stmt->get_result();

    $downloads = [];
    while ($row = $result->fetch_assoc()) $downloads[] = $row;
    json_ok($downloads);
}

json_err('Unknown action', 404);
