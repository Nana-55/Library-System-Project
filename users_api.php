<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/authz.php";

require_admin(true);

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, full_name, email, role, created_at FROM users ORDER BY id ASC");
    $stmt->execute();
    $res = $stmt->get_result();
    $users = [];
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    echo json_encode(["ok" => true, "users" => $users]);
    exit;
}

$postedToken = $_POST["csrf_token"] ?? $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
if (!is_string($postedToken) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $postedToken)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Invalid CSRF token."]);
    exit;
}

$action = $_POST["action"] ?? "";

if ($action === "delete") {
    $targetId = filter_var($_POST["id"] ?? 0, FILTER_VALIDATE_INT);
    if ($targetId === false || $targetId <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Invalid user ID."]);
        exit;
    }

    // Preventing an admin from deleting their own account (and keeping at least one admin)
    if (!can_delete_user((int)$targetId, $conn)) {
        http_response_code(403);
        echo json_encode(["ok" => false, "error" => "Action forbidden: cannot delete current admin or last admin account."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $targetId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Failed to delete user."]);
        exit;
    }

    echo json_encode(["ok" => true]);
    exit;
}

http_response_code(400);
echo json_encode(["ok" => false, "error" => "Unsupported action."]);