// Front-end validation only; the backend must validate all inputs again.
const menu = document.getElementById("menu");
const nav = document.getElementById("navlinks");
menu.addEventListener("click", () => {
  const open = nav.classList.toggle("open");
  menu.setAttribute("aria-expanded", String(open));
});
const form = document.getElementById("auth-form");
const feedback = document.getElementById("auth-feedback");
function message(text, error = false) {
  feedback.hidden = false;
  feedback.textContent = text;
  feedback.classList.toggle("error", error);
}
form.addEventListener("submit", (event) => {
  event.preventDefault();
  feedback.hidden = true;
  const data = new FormData(form);
  const email = String(data.get("email") || "").trim();
  const password = String(data.get("password") || "");
  if (!email || !form.elements.email.checkValidity()) {
    message("Please enter a valid email address.", true);
    return;
  }
  if (!password) {
    message("Please enter your password.", true);
    return;
  }
  if (form.dataset.mode === "register") {
    const name = String(data.get("name") || "").trim();
    if (name.length < 2) {
      message("Please enter your full name (at least 2 characters).", true);
      return;
    }
    if (password.length < 8) {
      message("Your password must contain at least 8 characters.", true);
      return;
    }
    if (password !== String(data.get("confirm") || "")) {
      message("Passwords do not match.", true);
      return;
    }
  }
  message(
    "The form looks good! Account creation and sign-in will work after the PHP/MySQL backend is connected.",
  );
});
