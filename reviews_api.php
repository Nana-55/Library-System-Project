<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/authz.php";

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "GET") {
    $bookId = isset($_GET["book_id"]) ? filter_var($_GET["book_id"], FILTER_VALIDATE_INT) : false;

    if ($bookId !== false && $bookId > 0) {
        $stmt = $conn->prepare(
            "SELECT r.id, r.book_id, r.user_id, u.full_name, b.title AS book_title, r.rating, r.comment, r.created_at
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             JOIN books b ON r.book_id = b.id
             WHERE r.book_id = ?
             ORDER BY r.id DESC"
        );
        $stmt->bind_param("i", $bookId);
    } else {
        $stmt = $conn->prepare(
            "SELECT r.id, r.book_id, r.user_id, u.full_name, b.title AS book_title, r.rating, r.comment, r.created_at
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             JOIN books b ON r.book_id = b.id
             ORDER BY r.id DESC"
        );
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $reviews = [];
    while ($row = $res->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();

    echo json_encode(["ok" => true, "reviews" => $reviews]);
    exit;
}

require_login(true);

$postedToken = $_POST["csrf_token"] ?? $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
if (!is_string($postedToken) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $postedToken)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Invalid CSRF token."]);
    exit;
}

$action = $_POST["action"] ?? "create";
$currentUserId = (int)$_SESSION["user_id"];

if ($action === "create") {
    $bookId = filter_var($_POST["book_id"] ?? 0, FILTER_VALIDATE_INT);
    $rating = filter_var($_POST["rating"] ?? 0, FILTER_VALIDATE_INT);
    $comment = trim($_POST["comment"] ?? "");

    if ($bookId === false || $bookId <= 0 || $rating === false || $rating < 1 || $rating > 5 || $comment === "") {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Invalid review input."]);
        exit;
    }

    // Ensure the referenced book exists before inserting a review
    $bookCheck = $conn->prepare("SELECT id FROM books WHERE id = ?");
    $bookCheck->bind_param("i", $bookId);
    $bookCheck->execute();
    $bookExists = (bool)$bookCheck->get_result()->fetch_assoc();
    $bookCheck->close();

    if (!$bookExists) {
        http_response_code(404);
        echo json_encode(["ok" => false, "error" => "Book not found."]);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO reviews (book_id, user_id, rating, comment)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("iiis", $bookId, $currentUserId, $rating, $comment);
    $ok = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Failed to create review."]);
        exit;
    }

    echo json_encode(["ok" => true, "id" => $newId]);
    exit;
}

if ($action === "update" || $action === "delete") {
    $reviewId = filter_var($_POST["id"] ?? 0, FILTER_VALIDATE_INT);
    if ($reviewId === false || $reviewId <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Invalid review ID."]);
        exit;
    }

    // Verifying the existence of the review and retrieving its true owner from the database
    $check = $conn->prepare("SELECT user_id FROM reviews WHERE id = ?");
    $check->bind_param("i", $reviewId);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(["ok" => false, "error" => "Review not found."]);
        exit;
    }

    if (!can_modify_record((int)$row["user_id"])) {
        http_response_code(403);
        echo json_encode(["ok" => false, "error" => "Forbidden: you do not own this review."]);
        exit;
    }

    if ($action === "delete") {
        $del = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $del->bind_param("i", $reviewId);
        $ok = $del->execute();
        $del->close();

        if (!$ok) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Failed to delete review."]);
            exit;
        }

        echo json_encode(["ok" => true]);
        exit;
    }

    $rating = filter_var($_POST["rating"] ?? 0, FILTER_VALIDATE_INT);
    $comment = trim($_POST["comment"] ?? "");
    if ($rating === false || $rating < 1 || $rating > 5 || $comment === "") {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Invalid review update."]);
        exit;
    }

    $upd = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
    $upd->bind_param("isi", $rating, $comment, $reviewId);
    $ok = $upd->execute();
    $upd->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Failed to update review."]);
        exit;
    }

    echo json_encode(["ok" => true]);
    exit;
}

http_response_code(400);
echo json_encode(["ok" => false, "error" => "Unsupported action."]);