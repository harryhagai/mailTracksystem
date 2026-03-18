<?php session_start(); ?>
<?php
$page_title = 'Register';
$base_url = '../';
include '../includes/header.php';
?>

<!-- Code Snowfall Animation -->
<div class="code-snow">
    <span>function()</span>
    <span>var x = 5;</span>
    <span>console.log()</span>
    <span>if (true) {}</span>
    <span>return value;</span>
    <span>for (i=0; i<10; i++)</span>
    <span>while (condition)</span>
    <span>document.getElementById()</span>
    <span>addEventListener()</span>
    <span>JSON.parse()</span>
    <span>JSON.stringify()</span>
    <span>setTimeout()</span>
    <span>setInterval()</span>
    <span>fetch().then()</span>
    <span>async function()</span>
    <span>await promise</span>
    <span>try { } catch(e) { }</span>
    <span>localStorage.setItem()</span>
    <span>sessionStorage.getItem()</span>
    <span>Array.push()</span>
</div>

<!-- Particles Canvas (moved here for particles.js) -->
<canvas id="particles-js"></canvas>

<div class="auth-container w-100">
    <div class="row justify-content-center w-100 g-0">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="auth-card">
                <div class="card-header text-center py-1 py-md-2">
                    <div class="logo mb-1">
                        <i class="bi bi-person-plus display-3 text-green"></i>
                    </div>
                    <h2 class="mb-1">Create Account</h2>
                    <p class="text-secondary mb-1">Join MailTrack - Professional Email Tracking</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger fade-in">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $_SESSION['error'];
                            unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="../actions/register.php" method="POST" class="needs-validation" novalidate>
                        <div class="mb-1">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" name="email" class="form-control"
                                    placeholder="your-email@company.com" required>
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Create strong password" required minlength="6">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-check"></i>
                                </span>
                                <input type="password" name="confirm_password" class="form-control"
                                    placeholder="Confirm your password" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-success btn-lg">
                                <i class="bi bi-person-plus me-2"></i>
                                Create Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-1">
                        <p class="mb-1 text-muted small">Already have an account?</p>
                        <a href="login.php" class="text-success fw-semibold text-decoration-none">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Compact footer for register page -->
<footer class="login-footer text-center py-2 text-muted small">
    <div class="container">
        <p class="mb-1">© <?php echo date('Y'); ?> MailTrack. All rights reserved. | Developed by HAGAI HAROLD NGOBEY</p>
        <p class="mb-0">Professional Email Tracking System</p>
    </div>
</footer>
<script src="../assets/js/particles.js"></script>
<?php include '../includes/footer.php'; ?>
