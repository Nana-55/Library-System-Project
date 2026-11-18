<?php

require_once "includes/session.php";

// Verify CSRF token

if (

    $_SERVER["REQUEST_METHOD"] !== "POST" ||

    !isset($_POST["csrf_token"]) ||

    !isset($_SESSION["csrf_token"]) ||

    !hash_equals(

        $_SESSION["csrf_token"],

        $_POST["csrf_token"]

    )

) {

    http_response_code(403);

    die("Invalid request.");

}

// Remove all session variables

$_SESSION = [];

// Delete session cookie

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(

        session_name(),

        "",

        [

            "expires" => time() - 42000,

            "path" => $params["path"],

            "domain" => $params["domain"],

            "secure" => $params["secure"],

            "httponly" => $params["httponly"],

            "samesite" => "Strict"

        ]

    );

}

// Destroy the session

session_destroy();

// Return to login page

header("Location: login.html");

exit;