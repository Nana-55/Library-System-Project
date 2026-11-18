<?php



require_once "includes/db.php";

require_once "includes/session.php";

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
    $full_name = trim($_POST["name"] ?? "");

    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";

    $confirm_password = $_POST["confirm"] ?? "";

    if (

        $full_name === "" ||

        !filter_var($email, FILTER_VALIDATE_EMAIL)

    ) {

        $message = "Please enter valid information.";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $check = $conn->prepare(

            "SELECT id FROM users WHERE email = ?"

        );

        $check->bind_param("s", $email);

        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Unable to create account with these details.";

        } else {

            $hashed_password = password_hash(

                $password,

                PASSWORD_DEFAULT

            );

            $stmt = $conn->prepare(

                "INSERT INTO users (full_name, email, password)

                 VALUES (?, ?, ?)"

            );

            $stmt->bind_param(

                "sss",

                $full_name,

                $email,

                $hashed_password

            );

            if ($stmt->execute()) {

                unset($_SESSION["csrf_token"]);

                header("Location: login.php?registered=1");

                exit;

            } else {

                $message = "Registration failed. Please try again.";

            }

            $stmt->close();

        }

        $check->close();

    }

    }
}

?>
 <!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Create account | Lumina Library</title>
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
        ><a class="nav-login" href="login.php">Sign in</a>
      </nav>
    </header>
    <main class="auth-layout wrap">
      <section class="auth-story">
        <div class="eyebrow">✦ YOUR DIGITAL READING SPACE</div>
        <h1>Every great story <em>begins somewhere.</em></h1>
        <p>
          Join a community of curious readers. Discover timeless books and share
          what inspires you.
        </p>
        <div class="auth-art">
          <div class="auth-orbit" aria-hidden="true">✦</div>
        </div>
      </section>
      <section class="auth-card">
        <a class="auth-back" href="index.html">← Back to library</a>
        <div class="eyebrow">JOIN THE COMMUNITY</div>
        <h2>Create your account</h2>
        <p>Start exploring the Lumina universe.</p>
        <?php if ($message !== ""): ?>
          <p class="auth-feedback error" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
        <form class="auth-form" id="auth-form" data-mode="register" action="register.php" method="POST" novalidate>
          <label class="auth-field"
            >Full name<input
              type="text"
              name="name"
              autocomplete="name"
              placeholder="Your full name"
              minlength="2"
              maxlength="80"
              required /></label
          ><label class="auth-field"
            >Email address<input
              type="email"
              name="email"
              autocomplete="email"
              placeholder="you@example.com"
              maxlength="254"
              required /></label
          ><label class="auth-field"
            >Password<input
              type="password"
              name="password"
              autocomplete="new-password"
              placeholder="Create a password"
              minlength="8"
              required
            /><span class="hint"
              >At least 8 characters. Use a mix of letters, numbers and
              symbols.</span
            ></label><label class="auth-field"
            >Confirm password<input
              type="password"
              name="confirm"
              autocomplete="new-password"
              placeholder="Repeat your password"
              required
          /></label>
          <p
            class="auth-feedback"
            id="auth-feedback"
            role="status"
            aria-live="polite"
            hidden
          ></p>
          <input

    type="hidden"

    name="csrf_token"

    value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"

/>
          <button type="submit" class="btn primary auth-submit">
            Create account <span>↗</span>
          </button>
        </form>
        <div class="auth-switch">
          Already have an account? <a href="login.php">Sign in</a>
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
