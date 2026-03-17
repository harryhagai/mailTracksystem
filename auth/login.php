<?php session_start(); ?>
<?php
$page_title = 'Login';
$base_url = '../';
include '../includes/header.php';
?>

<!-- Particles Canvas (moved here for particles.js) -->
<canvas id="particles-js"></canvas>

<div class="auth-container w-100">
    <div class="row justify-content-center w-100 g-0">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="auth-card">
                <div class="card-header text-center py-1 py-md-2">
                    <div class="logo mb-1">
                        <i class="bi bi-envelope-check display-3 text-green"></i>
                    </div>
                    <h2 class="mb-1">Welcome to MailTrack</h2>
                    <p class="text-secondary mb-1">Professional Email Follow-up System</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger fade-in">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $_SESSION['error'];
                            unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success fade-in">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="../actions/login.php" method="POST" id="loginForm" class="needs-validation" novalidate>
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

                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Enter your password" required minlength="6">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Sign In
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-1">
                        <p class="mb-1 text-muted small">Don't have an account?</p>
                        <a href="register.php" class="text-green fw-semibold text-decoration-none">
                            <i class="bi bi-person-plus me-1"></i>Create Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Compact footer for login page -->
<footer class="login-footer text-center py-2 text-muted small">
    <div class="container">
        <p class="mb-1">© <?php echo date('Y'); ?> MailTrack. All rights reserved. | Developed by HAGAI HAROLD NGOBEY</p>
        <p class="mb-0">Professional Email Tracking System</p>
    </div>
</footer>
