<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    $user = null;
}
?>
<?php 
$page_title = 'Profile';
$active_page = 'profile';
$base_url = '../';
include '../includes/header.php'; 
?>

<!-- Page Header -->
<div class="row align-items-center justify-content-between mb-5">
    <div class="col">
        <h1 class="mb-2">
            <i class="bi bi-person-circle me-2"></i>
            Profile Settings
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-light mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Profile</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Alerts -->
<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-5">
    <!-- Profile Main Content -->
    <div class="col-xl-8">
        <!-- Profile Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-5">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2rem;">
                            <?php echo strtoupper(substr($_SESSION['email'], 0, 2)); ?>
                        </div>
                    </div>
                    <div class="col">
                        <h3 class="mb-1"><?php echo htmlspecialchars($_SESSION['email']); ?></h3>
                        <p class="text-muted mb-0">Member since <?php echo $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-shield-check me-2"></i>
                    Security & Password
                </h5>
            </div>
            <div class="card-body">
                <form action="actions/update_profile.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="new_password" class="form-control" required minlength="8">
                            </div>
                            <div class="form-text">Minimum 8 characters</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-check"></i></span>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-top">
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="bi bi-check2-circle me-2"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-xl-4">
        <!-- Account Overview -->
        <div class="card border-0 shadow-sm h-100 mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">Account Overview</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 border-0 py-3">
                        <div class="row align-items-center mb-2">
                            <div class="col-auto">
                                <i class="bi bi-hash text-muted fs-5"></i>
                            </div>
                            <div class="col">
                                <small class="text-muted">User ID</small>
                                <div class="fw-semibold"><?php echo $_SESSION['user_id']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-0 border-0 py-3 border-top">
                        <div class="row align-items-center mb-2">
                            <div class="col-auto">
                                <i class="bi bi-envelope text-muted fs-5"></i>
                            </div>
                            <div class="col">
                                <small class="text-muted">Primary Email</small>
                                <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-0 border-0 py-3 border-top">
                        <div class="row align-items-center mb-2">
                            <div class="col-auto">
                                <i class="bi bi-calendar-check text-success fs-5"></i>
                            </div>
                            <div class="col">
                                <small class="text-muted">Member Since</small>
                                <div class="fw-semibold"><?php echo $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Stats -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Usage Stats</h6>
            </div>
            <div class="card-body pt-4">
                <div class="row text-center">
                    <div class="col-6 pb-3 border-end">
                        <div class="fs-3 fw-bold text-primary mb-1">150</div>
                        <div class="text-muted small">Emails Tracked</div>
                    </div>
                    <div class="col-6 pb-3">
                        <div class="fs-3 fw-bold text-success mb-1">98%</div>
                        <div class="text-muted small">Success Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a href="emails.php" class="btn btn-outline-success">
                        <i class="bi bi-inbox me-2"></i>Emails
                    </a>
                    <a href="../auth/logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function exportData() {
    alert('Export functionality coming soon!');
}
</script>
