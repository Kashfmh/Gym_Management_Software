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
  adminLoginDiv.className = "login-box";

  // Set the inner HTML for the admin login form
  adminLoginDiv.innerHTML = `
      <div class="login-text">Admin Login</div>
      <div class="login-info">
        <div class="input-group">
          <input type="text" class="input-field" placeholder="Admin Username" />
        </div>
        <div class="input-group">
          <input type="password" class="input-field" placeholder="Password" />
        </div>
        <button class="login-button">Login</button>
        <div class="signup-text">
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
