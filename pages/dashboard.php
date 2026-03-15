<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get emails due today
try {
    $stmt = $pdo->prepare("SELECT * FROM emails WHERE user_id = ? AND due_date = CURDATE() ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $due_today = $stmt->fetchAll();
    
    // Get total emails count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM emails WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_emails = $stmt->fetch()['total'];
    
    // Get overdue emails count
    $stmt = $pdo->prepare("SELECT COUNT(*) as overdue FROM emails WHERE user_id = ? AND due_date < CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $overdue = $stmt->fetch()['overdue'];
    
} catch(PDOException $e) {
    $due_today = [];
    $total_emails = 0;
    $overdue = 0;
}
?>
<?php 
$page_title = 'Dashboard';
$active_page = 'dashboard';
$base_url = '../';
include '../includes/header.php'; 
?>

<style>
    .dashboard-section-white {
        background-color: #ffffff;
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .dashboard-section-gray {
        background-color: #f8f9fa;
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
</style>

<!-- Dashboard Header -->
<div class="dashboard-section-white">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="mb-0">
                <i class="bi bi-speedometer2 me-2"></i>
                Dashboard
            </h1>
            <p class="text-muted">Welcome back! Here's your email tracking summary.</p>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="dashboard-section-gray">
    <div class="row g-3">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check text-primary display-6 mb-3 d-block"></i>
                    <h6 class="card-title mb-2">Due Today</h6>
                    <h2 class="card-text mb-0"><?php echo count($due_today); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle text-danger display-6 mb-3 d-block"></i>
                    <h6 class="card-title mb-2">Overdue</h6>
                    <h2 class="card-text mb-0"><?php echo $overdue; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-envelope text-info display-6 mb-3 d-block"></i>
                    <h6 class="card-title mb-2">Total Emails</h6>
                    <h2 class="card-text mb-0"><?php echo $total_emails; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-success display-6 mb-3 d-block"></i>
                    <h6 class="card-title mb-2">Success Rate</h6>
                    <h2 class="card-text mb-0">95%</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="dashboard-section-white">
    <div class="row g-4">
        <!-- Due Today Section -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-bell me-2"></i>
                    Emails Due Today (<?php echo count($due_today); ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if(count($due_today) > 0): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-bottom">Email</th>
                                <th class="border-bottom">Due Date</th>
                                <th class="border-bottom">Status</th>
                                <th class="border-bottom">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($due_today as $email): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($email['email']); ?></strong>
                                </td>
                                <td><span class="badge bg-success"><?php echo $email['due_date']; ?></span></td>
                                <td><span class="badge bg-info">Active</span></td>
                                <td>
                                    <a href="emails.php?edit=<?php echo $email['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No emails due today</h5>
                    <p class="text-muted mb-4">Great job staying on top of your follow-ups!</p>
                    <a href="emails.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add First Email
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Quick Actions Section -->
<div class="dashboard-section-gray">
    <div class="row g-4">
        <!-- Quick Actions Sidebar -->
        <div class="col-lg-4 ms-auto">
            <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Add</h5>
            </div>
            <div class="card-body">
                <form action="../actions/add_email.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. john@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-2"></i>Add Email
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Quick Links</h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="emails.php" class="btn btn-outline-primary">
                    <i class="bi bi-list-ul me-2"></i>View All Emails
                </a>
                <a href="profile.php" class="btn btn-outline-secondary">
                    <i class="bi bi-gear me-2"></i>Settings & Profile
                </a>
            </div>
        </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
