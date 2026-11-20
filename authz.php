<?php

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . "/session.php";
}

function is_logged_in(): bool
{
    return isset($_SESSION["user_id"]) && (int)$_SESSION["user_id"] > 0;
}

function is_admin(): bool
{
    return is_logged_in() && (($_SESSION["role"] ?? "user") === "admin");
}

function require_login(bool $json = false): void
{
    if (!is_logged_in()) {
        if ($json) {
            http_response_code(401);
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => false, "error" => "Authentication required."]);
            exit;
        }
        header("Location: login.php");
        exit;
    }
}

function require_admin(bool $json = false): void
{
    if (!is_logged_in()) {
        if ($json) {
            http_response_code(401);
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => false, "error" => "Authentication required."]);
            exit;
        }
        header("Location: login.php");
        exit;
    }

    if (!is_admin()) {
        http_response_code(403);
        if ($json) {
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => false, "error" => "Access denied: admin privileges required."]);
            exit;
        }
        die("Access denied: you do not have permission to perform this action.");
    }
}

function can_modify_record(int $record_user_id): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $current_user_id = (int)$_SESSION["user_id"];
    return is_admin() || ($current_user_id === $record_user_id);
}

function can_delete_user(int $target_user_id, ?mysqli $conn = null): bool
{
    if (!is_admin()) {
        return false;
    }

    $current_user_id = (int)$_SESSION["user_id"];
    if ($current_user_id === $target_user_id) {
        return false;
    }

    // Optional safeguard: prevent deleting the last remaining admin account if $conn is provided
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS admin_count FROM users WHERE role = 'admin' AND id <> ?");
        if ($stmt) {
            $stmt->bind_param("i", $target_user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ((int)($row["admin_count"] ?? 0) < 1) {
                return false;
            }
        }
    }

    return true;
}