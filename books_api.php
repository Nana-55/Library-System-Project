<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/authz.php";

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "GET") {
    $search = trim($_GET["q"] ?? "");
    $genre = trim($_GET["genre"] ?? "");

    if ($search !== "" && $genre !== "" && strtolower($genre) !== "all") {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare(
            "SELECT id, title, author, genre, publication_year, description, cover_image, created_at
             FROM books
             WHERE (title LIKE ? OR author LIKE ?) AND genre = ?
             ORDER BY id DESC"
        );
        $stmt->bind_param("sss", $like, $like, $genre);
    } elseif ($search !== "") {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare(
            "SELECT id, title, author, genre, publication_year, description, cover_image, created_at
             FROM books
             WHERE title LIKE ? OR author LIKE ?
             ORDER BY id DESC"
        );
        $stmt->bind_param("ss", $like, $like);
    } elseif ($genre !== "" && strtolower($genre) !== "all") {
        $stmt = $conn->prepare(
            "SELECT id, title, author, genre, publication_year, description, cover_image, created_at
             FROM books
             WHERE genre = ?
             ORDER BY id DESC"
        );
        $stmt->bind_param("s", $genre);
    } else {
        $stmt = $conn->prepare(
            "SELECT id, title, author, genre, publication_year, description, cover_image, created_at
             FROM books
             ORDER BY id DESC"
        );
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $books = [];
    while ($row = $res->fetch_assoc()) {
        $books[] = $row;
    }
    $stmt->close();

    echo json_encode(["ok" => true, "books" => $books]);
    exit;
}

// Admin + CSRF
require_admin(true);

$postedToken = $_POST["csrf_token"] ?? $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
if (!is_string($postedToken) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $postedToken)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Invalid CSRF token."]);
    exit;
}

$action = $_POST["action"] ?? "create";
$maxYear = (int)date("Y");
$allowedExtensions = ["png", "jpg", "jpeg", "webp", "gif"];

if ($action === "create") {
    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $year = filter_var($_POST["year"] ?? 0, FILTER_VALIDATE_INT);
    $description = trim($_POST["description"] ?? "");
    $cover = basename(trim($_POST["cover_image"] ?? "little-women.png"));
    $ext = strtolower(pathinfo($cover, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        $cover = "little-women.png";
    }

    if ($title === "" || $author === "" || $genre === "" || $description === "" || $year === false || $year < 1000 || $year > $maxYear) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Invalid book data."]);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO books (title, author, genre, publication_year, description, cover_image)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssiss", $title, $author, $genre, $year, $description, $cover);
    $ok = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Failed to create book."]);
        exit;
    }

    echo json_encode(["ok" => true, "id" => $newId]);
    exit;
}

if ($action === "update") {
    $id = filter_var($_POST["id"] ?? 0, FILTER_VALIDATE_INT);
    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $year = filter_var($_POST["year"] ?? 0, FILTER_VALIDATE_INT);
    $description = trim($_POST["description"] ?? "");

    if ($id === false || $id <= 0 || $title === "" || $author === "" || $genre === "" || $description === "" || $year === false || $year < 1000 || $year > $maxYear) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Invalid update input."]);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE books
         SET title = ?, author = ?, genre = ?, publication_year = ?, description = ?
         WHERE id = ?"
    );
    $stmt->bind_param("sssisi", $title, $author, $genre, $year, $description, $id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Failed to update book."]);
        exit;
    }

    echo json_encode(["ok" => true]);
    exit;
}

if ($action === "delete") {
    $id = filter_var($_POST["id"] ?? 0, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Invalid book ID."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Failed to delete book."]);
        exit;
    }

    echo json_encode(["ok" => true]);
    exit;
}

http_response_code(400);
echo json_encode(["ok" => false, "error" => "Unsupported action."]);