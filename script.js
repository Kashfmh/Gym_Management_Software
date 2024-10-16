function showRegisterForm() {
  event.preventDefault();
  document.getElementById("login-form").style.display = "none";
  document.getElementById("register-form").style.display = "block";
}

function showLoginForm() {
  event.preventDefault();
  document.getElementById("register-form").style.display = "none";
  document.getElementById("login-form").style.display = "block";
}

function showAdminLoginForm() {
  // Clear the current content of the body
  document.body.innerHTML = "";

  // Create a new div for the admin login form
  const adminLoginDiv = document.createElement("div");
  adminLoginDiv.className = "login-box-admin";

  // Set the inner HTML for the admin login form
  adminLoginDiv.innerHTML = `
      <form method="POST" action="">
      <span class="admin-txt">ADMIN LOGIN FORM</span>
          <div class="input-group">
            <input type="email" name="admin_email" class="input-field" placeholder="Email" required />
          </div>
          <div class="input-group">
            <input type="password" name="admin_password" class="input-field" placeholder="Password" required />
          </div>
          <button type="submit" name="admin_login" class="login-button-admin">Login</button>
        </form>
        <div class="back-to-homepage-text">
          <a href="#" onclick="goBackToHomepage()">Go back to homepage</a>
        </div>
      </div>
    `;

  // Append the admin login form to the body
  document.body.appendChild(adminLoginDiv);

  // Apply styles to match the current theme
  document.body.style.backgroundColor = "#edf2f4";
  document.body.style.display = "flex";
  document.body.style.justifyContent = "center";
  document.body.style.alignItems = "center";
  document.body.style.height = "100vh";
}

function goBackToHomepage() {
  location.reload();
}

document.addEventListener("DOMContentLoaded", function () {
  const registerForm = document.getElementById("register-form");
  if (registerForm && window.location.hash === "#register-form") {
    registerForm.scrollIntoView();
  }
});
