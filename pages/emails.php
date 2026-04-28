<?php
require '../config/db.php';
require_login();

// Get all emails for the current user
try {
    $stmt = $pdo->prepare("SELECT * FROM emails WHERE user_id = ? ORDER BY due_date ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $emails = $stmt->fetchAll();

    $edit_email = null;
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $pdo->prepare("SELECT * FROM emails WHERE id = ? AND user_id = ?");
        $stmt->execute([$edit_id, $_SESSION['user_id']]);
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

<style>
    .emails-dark .table,
    .emails-dark .table th,
    .emails-dark .table td {
        background-color: transparent !important;
    }

    .emails-dark .table thead th {
        background-color: rgba(25, 135, 84, 0.08) !important;
        color: #1f5134;
        border-bottom-color: rgba(25, 135, 84, 0.16);
    }

    .emails-dark .table tbody tr {
        background-color: #ffffff !important;
    }

    .emails-dark .table tbody tr.table-danger,
    .emails-dark .table tbody tr.table-warning {
        background-color: #fdfdfd !important;
    }

    .emails-dark .table tbody tr:hover {
        background-color: rgba(25, 135, 84, 0.06) !important;
    }

    .emails-dark .table tbody td,
    .emails-dark .table tbody td ,
    .emails-dark .table tbody td strong {
        color: #173224;
    }

    .mt-modal {
        position: fixed;
        inset: 0;
        display: none;
        z-index: 2000;
    }

    .mt-modal.is-open {
        display: block;
    }

    .mt-modal__backdrop {
        position: fixed;
        inset: 0;
        background: rgba(23, 50, 36, 0.35);
    }

    .mt-modal__dialog {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: min(520px, 92vw);
        max-height: 90vh;
        overflow: auto;
        background: #ffffff;
        color: #173224;
        border: 1px solid rgba(25, 135, 84, 0.16);
        border-radius: 14px;
        box-shadow: none;
        z-index: 1;
    }

    .mt-modal__header,
    .mt-modal__footer {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid rgba(25, 135, 84, 0.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .mt-modal__footer {
        border-top: 1px solid rgba(25, 135, 84, 0.12);
        border-bottom: none;
        justify-content: flex-end;
        gap: 0.6rem;
    }

    .mt-modal__body {
        padding: 1rem 1.25rem 0.5rem;
    }

    .mt-modal__title,
    .mt-modal .form-label,
    .mt-modal .input-group-text,
    .mt-modal .form-control {
        color: #173224;
    }

    .mt-modal__close {
        background: #ffffff;
        border: 1px solid rgba(25, 135, 84, 0.16);
        color: #198754;
        border-radius: 10px;
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    body.modal-open {
        overflow: hidden;
    }
</style>

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
        <div class="d-flex gap-2 flex-wrap align-items-center justify-content-end">
            <button type="button"
                    class="btn btn-outline-success"
                    data-modal-target="#addEmailModal">
                <i class="bi bi-plus-circle me-2"></i>Add Email
            </button>
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
        <?php echo e($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Emails List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm emails-dark">
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
                                            <div><?php echo e($email['email']); ?></div>
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
                                    <span class="<?php echo $is_overdue ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $is_overdue ? abs($days_diff).' days late' : ($days_diff > 0 ? round($days_diff).' days' : 'Today'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-success"
                                                title="Update"
                                                data-modal-target="#editEmailModal"
                                                data-id="<?php echo e($email['id']); ?>"
                                                data-email="<?php echo e($email['email']); ?>"
                                                data-date="<?php echo e($email['due_date']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="../actions/delete_email.php" method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo e($email['id']); ?>">
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Delete"
                                                    onclick="return confirm('Delete this follow-up?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

<!-- Add Email Modal -->
<div class="mt-modal" id="addEmailModal" aria-hidden="true" role="dialog" aria-labelledby="addEmailLabel">
    <div class="mt-modal__backdrop" data-modal-close></div>
    <div class="mt-modal__dialog">
        <div class="mt-modal__header">
            <h5 class="mt-modal__title" id="addEmailLabel"><i class="bi bi-plus-circle me-2"></i>Add New Email</h5>
            <button type="button" class="mt-modal__close" data-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="../actions/add_email.php" method="POST" class="needs-validation" novalidate>
            <?php echo csrf_field(); ?>
            <div class="mt-modal__body">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="john@company.com" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Follow-up Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="mt-modal__footer">
                <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-lg me-2"></i>Add Email
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Email Modal -->
<div class="mt-modal" id="editEmailModal" aria-hidden="true" role="dialog" aria-labelledby="editEmailLabel">
    <div class="mt-modal__backdrop" data-modal-close></div>
    <div class="mt-modal__dialog">
        <div class="mt-modal__header">
            <h5 class="mt-modal__title" id="editEmailLabel"><i class="bi bi-pencil-square me-2"></i>Update Email</h5>
            <button type="button" class="mt-modal__close" data-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="../actions/update_email.php" method="POST" class="needs-validation" novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="edit-email-id">
            <div class="mt-modal__body">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="edit-email" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Follow-up Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="due_date" id="edit-date" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="mt-modal__footer">
                <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-check2 me-2"></i>Update Email
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($edit_email)): ?>
<script>
    window.MailTrackEditPrefill = <?php echo json_encode([
        'id' => $edit_email['id'],
        'email' => $edit_email['email'],
        'due_date' => $edit_email['due_date']
    ]); ?>;
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
