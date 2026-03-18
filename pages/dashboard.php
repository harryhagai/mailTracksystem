<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Prep chart labels for next 7 days
$chart_labels = [];
$chart_counts = [];
$chart_date_keys = [];
for ($i = 0; $i < 7; $i++) {
    $date_key = date('Y-m-d', strtotime("+$i day"));
    $chart_date_keys[] = $date_key;
    $chart_labels[] = date('M j', strtotime($date_key));
    $chart_counts[] = 0;
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

    // Chart data: follow-ups due in the next 7 days
    $stmt = $pdo->prepare("
        SELECT due_date, COUNT(*) as total
        FROM emails
        WHERE user_id = ?
          AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
        GROUP BY due_date
        ORDER BY due_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll();
    $date_index = array_flip($chart_date_keys);
    foreach ($rows as $row) {
        $key = $row['due_date'];
        if (isset($date_index[$key])) {
            $chart_counts[$date_index[$key]] = (int)$row['total'];
        }
    }
    
} catch(PDOException $e) {
    $due_today = [];
    $total_emails = 0;
    $overdue = 0;
    $chart_counts = array_fill(0, count($chart_labels), 0);
}

$due_today_total = count($due_today);
$due_today_limited = array_slice($due_today, 0, 5);
?>
<?php 
$page_title = 'Dashboard';
$active_page = 'dashboard';
$base_url = '../';
include '../includes/header.php'; 
?>

<style>
    .dashboard-section-gray {
        background-color: transparent;
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }

    .hacker-card {
        background: linear-gradient(135deg, rgba(3, 8, 4, 0.98), rgba(6, 20, 8, 0.98));
        border: 1px solid rgba(20, 127, 2, 0.4);
        box-shadow: 0 0 24px rgba(20, 127, 2, 0.25);
    }

    .hacker-card .card-header {
        background: transparent;
        color: #64ff64;
        border-bottom: 1px solid rgba(20, 127, 2, 0.35);
    }

    .hacker-chart-wrap {
        position: relative;
        height: 320px;
    }

    .hacker-chart-wrap canvas {
        position: relative;
        z-index: 2;
    }

    .hacker-chart-glow,
    .hacker-chart-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 1;
    }

    .hacker-chart-glow {
        background: radial-gradient(circle at 20% 20%, rgba(20, 127, 2, 0.2), transparent 60%),
                    radial-gradient(circle at 80% 10%, rgba(20, 255, 20, 0.12), transparent 55%);
        mix-blend-mode: screen;
    }

    .hacker-chart-grid {
        background-image:
            linear-gradient(rgba(20, 127, 2, 0.12) 1px, transparent 1px),
            linear-gradient(90deg, rgba(20, 127, 2, 0.12) 1px, transparent 1px);
        background-size: 24px 24px;
        opacity: 0.6;
        mix-blend-mode: screen;
        animation: hackerGridMove 12s linear infinite;
    }

    @keyframes hackerGridMove {
        0% {
            background-position: 0 0;
        }
        100% {
            background-position: -480px 0;
        }
    }

    .dashboard-stat-card .card-body {
        padding: 0.7rem;
    }

    .dashboard-stat-card .stat-icon {
        font-size: 1.15rem;
        margin-bottom: 0.35rem;
    }

    .dashboard-stat-card .card-title {
        font-size: 0.75rem;
        margin-bottom: 0.2rem;
    }

    .dashboard-stat-card .card-text {
        font-size: 1.05rem;
    }

    .due-today-header {
        background: transparent;
        color: #147f02;
        border: 1px solid #147f02;
    }
</style>

<!-- Stats Cards -->
<div class="dashboard-section-gray">
    <div class="row g-3">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check text-primary stat-icon d-block"></i>
                    <h6 class="card-title mb-2">Due Today</h6>
                    <h2 class="card-text mb-0"><?php echo count($due_today); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle text-danger stat-icon d-block"></i>
                    <h6 class="card-title mb-2">Overdue</h6>
                    <h2 class="card-text mb-0"><?php echo $overdue; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-envelope text-info stat-icon d-block"></i>
                    <h6 class="card-title mb-2">Total Emails</h6>
                    <h2 class="card-text mb-0"><?php echo $total_emails; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-success stat-icon d-block"></i>
                    <h6 class="card-title mb-2">Success Rate</h6>
                    <h2 class="card-text mb-0">95%</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="dashboard-section-gray">
    <div class="row g-4 align-items-stretch">
        <!-- Hacker Activity Graph (Left) -->
        <div class="col-lg-8">
            <div class="card hacker-card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Hacker Activity Graph</h5>
                </div>
                <div class="card-body">
                    <div class="hacker-chart-wrap">
                        <div class="hacker-chart-glow"></div>
                        <div class="hacker-chart-grid"></div>
                        <canvas id="hackerChart" aria-label="Follow-up activity graph" role="img"></canvas>
                    </div>
                    <div class="d-flex justify-content-between mt-3 text-muted small">
                        <span>Next 7 days</span>
                        <span>Green = follow-ups due</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Due Today Section (Right) -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
            <div class="card-header due-today-header">
                <h5 class="mb-0">
                    <i class="bi bi-bell me-2"></i>
                    Emails Due Today (<?php echo $due_today_total; ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if($due_today_total > 0): ?>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-bottom">Email</th>
                                <th class="border-bottom text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($due_today_limited as $email): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($email['email']); ?></strong>
                                </td>
                                <td class="text-end">
                                    <a href="emails.php?edit=<?php echo $email['id']; ?>" class="btn btn-sm btn-outline-success" title="Update">
                                        <i class="bi bi-pencil"></i>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const canvas = document.getElementById('hackerChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = <?php echo json_encode($chart_labels); ?>;
    const values = <?php echo json_encode($chart_counts); ?>;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Follow-ups Due',
                data: values,
                borderColor: '#1bff1b',
                backgroundColor: 'rgba(27, 255, 27, 0.15)',
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    borderColor: '#1bff1b',
                    borderWidth: 1,
                    titleColor: '#b8ffb8',
                    bodyColor: '#e2ffe2'
                }
            },
            scales: {
                x: {
                    ticks: { color: '#8cff8c' },
                    grid: { color: 'rgba(20, 127, 2, 0.2)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: '#8cff8c', precision: 0 },
                    grid: { color: 'rgba(20, 127, 2, 0.2)' }
                }
            }
        }
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
