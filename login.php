<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/session.php";

$message = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

if (!is_string($postedToken) || !hash_equals($_SESSION["csrf_token"], $postedToken)) {
        http_response_code(403);
        $message = "Invalid request. Please refresh the page and try again.";
         } else {
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

 if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
            $message = "Invalid email or password.";
        } else {
            $stmt = $conn->prepare("SELECT id, full_name, email, password, role, failed_attempts, locked_until FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $locked = $user && $user["locked_until"] !== null && strtotime($user["locked_until"]) > time();


 if (!$user || $locked || !password_verify($password, $user["password"])) {

 if ($user && !$locked) {
      $attempts = (int)$user["failed_attempts"] + 1;

 if ($attempts >= 5) {
     $update = $conn->prepare("UPDATE users SET failed_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
     $update->bind_param("i", $user["id"]);
     } else {
     $update = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?");
     $update->bind_param("ii", $attempts, $user["id"]);
    }
     $update->execute();
     $update->close();
    }

    $message = "Invalid email or password.";
     } else {
                $reset = $conn->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
                $reset->bind_param("i", $user["id"]);
                $reset->execute();
                $reset->close();
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["last_activity"] = time();
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                header("Location: " . ($user["role"] === "admin" ? "admin.php" : "index.html"));
                exit;
            }
            $stmt->close();
        }
    }
}
?>



<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Sign in | Lumina Library</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>

  <body>
    <div class="stars stars-a" aria-hidden="true"></div>
    <div class="stars stars-b" aria-hidden="true"></div>
    <header class="nav">
      <a class="brand" href="index.html#home"
        ><span class="brand-mark">✦</span
        ><span>LUMINA <small>LIBRARY</small></span></a
      ><button
        class="menu"
        id="menu"
        aria-label="Toggle navigation"
        aria-expanded="false"
      >
        ☰
      </button>
      <nav id="navlinks">
        <a href="index.html#home">Home</a
        ><a href="index.html#collection">Collection</a
        ><a href="index.html#about">About</a
        ><a href="index.html#reviews">Community</a
        ><a class="nav-login" href="register.php">Join Lumina</a>
      </nav>
    </header>
    <main class="auth-layout wrap">
      <section class="auth-story">
        <div class="eyebrow">✦ YOUR DIGITAL READING SPACE</div>
        <h1>Welcome back to your <em>reading universe.</em></h1>
        <p>
          Your next favorite story is waiting. Sign in to return to the books
          and conversations you love.
        </p>
        <div class="auth-art">
          <div class="auth-orbit" aria-hidden="true">✦</div>
        </div>
      </section>
      <section class="auth-card">
        <a class="auth-back" href="index.html">← Back to library</a>
        <div class="eyebrow">WELCOME BACK</div>
        <h2>Sign in</h2>
        <p>Enter your details to continue your journey.</p>
        <?php if (isset($_GET["registered"])): ?>
          <p class="auth-feedback" role="status">Account created. Please sign in.</p>
        <?php elseif (isset($_GET["timeout"])): ?>
          <p class="auth-feedback error" role="alert">Your session expired. Please sign in again.</p>
        <?php endif; ?>
        <form class="auth-form" id="auth-form" data-mode="login" action="login.php" method="POST" novalidate>
          <label class="auth-field"
            >Email address<input
              type="email"
              name="email"
              autocomplete="email"
              placeholder="you@example.com"
              required
              maxlength="254" /></label
          ><label class="auth-field"
            >Password<input
              type="password"
              name="password"
              autocomplete="current-password"
              placeholder="Enter your password"
              required
          /></label>
          <div class="auth-options">
            <label><input type="checkbox" name="remember" /> Remember me</label
            ><span
              >Forgot password?
              <span title="Available after backend integration"
                >(coming soon)</span
              ></span
            >
          </div>
          <?php if ($message !== ""): ?>
            <p class="auth-feedback error" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
          <?php endif; ?>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
          <button type="submit" class="btn primary auth-submit">
            Sign in <span>↗</span>
          </button>
        </form>
        <div class="auth-switch">
          New to Lumina? <a href="register.php">Create an account</a>
        </div>
        <div class="auth-demo">
          Front-end preview only. No credentials are sent, stored, or used to
          create accounts. Authentication, secure sessions and server-side
          validation will be connected by the PHP team.
        </div>
      </section>
    </main>
    <footer>
      <div class="wrap footer-inner">
        <a class="brand" href="index.html"
          ><span class="brand-mark">✦</span
          ><span>LUMINA <small>LIBRARY</small></span></a
        >
        <p>Explore more. Imagine beyond.</p>
        <span>© 2026 Lumina Library · Student project prototype</span>
      </div>
    </footer>
    <script src="js/auth.js"></script>
  </body>
</html>
