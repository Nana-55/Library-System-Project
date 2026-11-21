<?php
require_once __DIR__ . '/includes/db.php';

$baseDir = __DIR__ . '/book_files';
$realBaseDir = realpath($baseDir);

if ($realBaseDir === false) {
    http_response_code(500);
    echo "Storage directory unavailable.";
    exit;
}

// منع تمرير مسارات ملفات مباشرة من المستخدم
if (isset($_GET['file'])) {
    http_response_code(400);
    echo "Direct file access is not allowed.";
    exit;
}

$bookId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;

if ($bookId === false || $bookId <= 0) {
    http_response_code(400);
    echo "Invalid book ID.";
    exit;
}

$stmt = $conn->prepare("SELECT title, book_file FROM books WHERE id = ?");
$stmt->bind_param("i", $bookId);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book || empty($book['book_file'])) {
    http_response_code(404);
    echo "Book file not found.";
    exit;
}

// قائمة بيضاء بالملفات المسموحة والتحقق من عدم وجود محاولات اجتياز مسار
$allowedFiles = [
    'little-women.txt',
    'pride-and-prejudice.txt',
    'the-secret-garden.txt',
    'anne-of-green-gables.txt',
];

$dbFileName = basename($book['book_file']);

if (
    $dbFileName !== $book['book_file'] ||
    strpos($book['book_file'], '..') !== false ||
    !in_array($dbFileName, $allowedFiles, true)
) {
    http_response_code(403);
    echo "Access to this file is restricted.";
    exit;
}

$targetPath = $realBaseDir . DIRECTORY_SEPARATOR . $dbFileName;
$realTargetPath = realpath($targetPath);

// التأكد من أن المسار الفعلي يقع داخل مجلد book_files فقط
if (
    $realTargetPath === false ||
    strpos($realTargetPath, $realBaseDir . DIRECTORY_SEPARATOR) !== 0 ||
    !is_file($realTargetPath)
) {
    http_response_code(403);
    echo "Invalid file path.";
    exit;
}

$mode = (isset($_GET['mode']) && $_GET['mode'] === 'download') ? 'download' : 'view';

if ($mode === 'download') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $dbFileName . '"');
} else {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $dbFileName . '"');
}

header('X-Content-Type-Options: nosniff');
readfile($realTargetPath);
exit;