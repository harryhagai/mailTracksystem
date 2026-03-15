<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get all emails for the current user
try {
    $stmt = $pdo->prepare("SELECT * FROM emails WHERE user_id = ? ORDER BY due_date ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $emails = $stmt->fetchAll();
    
    // Get email for editing
    $edit_email = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare("SELECT * FROM emails WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['edit'], $_SESSION['user_id']]);
        $edit_email = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    $emails = [];
    $edit_email = null;
}
?>
<?php 
$page_title = 'Emails';
$active_page = 'emails';
$base_url = '../';
include '../includes/header.php'; 
?>

<!-- Page Header -->
<div class="row align-items-center justify-content-between mb-5 flex-wrap">
    <div class="col">
        <h1 class="mb-1">
            <i class="bi bi-inbox me-2"></i>
            Email Management
        </h1>
        <p class="text-muted mb-0">Manage your follow-up emails and reminders</p>
    </div>
    <div class="col-auto">
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-primary fs-6"><?php echo count($emails); ?> Total</span>
            <?php 
            $overdue_count = 0; $due_today_count = 0;
            foreach($emails as $email) {
                $today = date('Y-m-d');
                if($email['due_date'] < $today) $overdue_count++;
                elseif($email['due_date'] == $today) $due_today_count++;
            }
            ?>
            <span class="badge bg-danger fs-6"><?php echo $overdue_count; ?> Overdue</span>
            <span class="badge bg-warning text-dark fs-6"><?php echo $due_today_count; ?> Due Today</span>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Email Form -->
    <div class="col-xl-4 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-<?php echo $edit_email ? 'pencil-square' : 'plus-circle'; ?> me-2"></i>
                    <?php echo $edit_email ? 'Edit Email' : 'Add New Email'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form action="actions/<?php echo $edit_email ? 'update_email.php' : 'add_email.php'; ?>" method="POST" class="needs-validation" novalidate>
                    <?php if($edit_email): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_email['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $edit_email ? htmlspecialchars($edit_email['email']) : ''; ?>" 
                                   placeholder="john@company.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Follow-up Date</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="due_date" class="form-control" 
                                   value="<?php echo $edit_email ? $edit_email['due_date'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?php echo $edit_email ? 'check2' : 'plus-lg'; ?> me-2"></i>
                            <?php echo $edit_email ? 'Update Email' : 'Add Email'; ?>
                        </button>
                        <?php if($edit_email): ?>
                            <a href="emails.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Emails List -->
    <div class="col-xl-8 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-nested me-2"></i>
                    All Follow-ups (<?php echo count($emails); ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if(count($emails) > 0): ?>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Email</th>
                                <th>Follow-up Date</th>
                                <th>Status</th>
                                <th>Days Left</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($emails as $email): 
                                $today = date('Y-m-d');
                                $is_overdue = $email['due_date'] < $today;
                                $is_due_today = $email['due_date'] == $today;
                                $days_diff = (strtotime($email['due_date']) - strtotime($today)) / (60*60*24);
                            ?>
                            <tr class="<?php echo $is_overdue ? 'table-danger' : ($is_due_today ? 'table-warning' : ''); ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-envelope fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($email['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $is_overdue ? 'bg-danger' : ($is_due_today ? 'bg-warning text-dark' : 'bg-success'); ?>">
                                        <?php echo date('M j', strtotime($email['due_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $is_overdue ? 'bg-danger' : ($is_due_today ? 'bg-warning text-dark' : 'bg-info'); ?>">
                                        <?php echo $is_overdue ? 'Overdue' : ($is_due_today ? 'Today' : 'Upcoming'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $is_overdue ? 'text-danger fw-semibold' : 'text-success'; ?>">
                                        <?php echo $is_overdue ? abs($days_diff).' days late' : ($days_diff > 0 ? round($days_diff).' days' : 'Today'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="?edit=<?php echo $email['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="actions/delete_email.php?id=<?php echo $email['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Delete"
                                           onclick="return confirm('Delete this follow-up?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-6">
                    <i class="bi bi-inbox display-1 text-muted mb-4 d-block"></i>
                    <h4 class="text-muted mb-3">No follow-ups yet</h4>
                    <p class="text-muted mb-4">Start tracking your emails to never miss a follow-up</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function scrollToForm() {
    document.querySelector('.slide-up').scrollIntoView({ behavior: 'smooth' });
}
</script>
