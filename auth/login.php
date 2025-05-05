<?php
  include '../layout/headerLayout.php';

  // Show error messages if any
  if (isset($_GET['error'])) {
      $errorMsg = '';
      if ($_GET['error'] === 'wrong_password') {
          $errorMsg = 'Incorrect password. Please try again.';
      } elseif ($_GET['error'] === 'user_not_found') {
          $errorMsg = 'No account found with that email.';
      }
      if ($errorMsg) {
          echo '<div class="notification is-danger has-text-centered">' . htmlspecialchars($errorMsg) . '</div>';
      }
  }
?>

<section class="a-nav">
    <div class="buttons is-link none">
        <button id="themeToggle" class="button is-light none">
            <span class="icon">
                    <i class="fas fa-moon"></i>

             </span>
        </button>
                            
    </div>
</section>
    
<section class="hero is-fullheight a">
  <div class="hero-body">
    <div class="container">
      <div class="columns is-centered">
        <div class="column is-5-tablet is-4-desktop is-3-widescreen">
          <div class="box has-shadow" style="border-top: 4px solid #ff7b25;">
            <div class="has-text-centered mb-5">
              <span class="icon-text">
                <span class="title is-3 has-text-weight-bold">Sweet Bite</span>
              </span>
              <p class="subtitle is-5 mt-2">Sign in to your account</p>
            </div>

            
            <form action="login-pros.php" method="POST" onsubmit="return validateLoginForm()>
              <div class="field">
                <label class="label">Email</label>
                <div class="control has-icons-left">
                  <input class="input" type="email" name="email" placeholder="e.g. alex@example.com" required>
                  <span class="icon is-small is-left">
                    <i class="fas fa-envelope"></i>
                  </span>
                </div>
              </div>
              <div class="field">
                <label class="label">Password</label>
                <div class="control has-icons-left">
                  <input class="input" type="password" name="password" placeholder="********" required>
                  <span class="icon is-small is-left">
                    <i class="fas fa-lock"></i>
                  </span>
                </div>
                
              </div>
              
             
              <div class="field">
                <button class="button is-primary is-fullwidth" type="submit">
                  <span class="icon">
                    <i class="fas fa-sign-in-alt"></i>
                  </span>
                  <span>Login</span>
                </button>
              </div>
            </form>
            <div class="has-text-centered">
              <p class="is-size-7">Don't have an account? <a href="../onboarding.php">Sign up</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  function validateLoginForm() {
    
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value;
    const emailPattern = /^[^ ]+@[^ ]+\.[a-z]$/;

    if (email === "" || password === "") 
     {
      alert("All fields are required.");
      return false;
     }
    
    if (email === "") {
      alert("Email is required.");
      return false;
    }
    if (password === "") {
      alert("Password is required.");
      return false;
    }
    
    if (email.length > 50) {
      alert("Email must be less than 50 characters.");
      return false;
    }
    
    if (password.length > 50) {
      alert("Password must be less than 50 characters.");
      return false;
    }
    if (email.length < 5) {
      alert("Email must be at least 5 characters.");
      return false;
    }

    if (!emailPattern.test(email)) {
      alert("Please enter a valid email address.");
      return false;
    }

    if (password.length < 6) {
      alert("Password must be at least 6 characters.");
      return false;
    }

    return true;
  }
</script>


<?php
  include '../layout/footerLayout.php'
?>
