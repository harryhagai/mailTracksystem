<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'MailTrack System'; ?> - MailTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="<?php echo $base_url ?? '../'; ?>assets/css/style.css" rel="stylesheet">
</head>
<?php $is_auth = strpos($_SERVER['REQUEST_URI'], 'auth') !== false; ?>
<body class="app-body <?php echo $is_auth ? 'app--auth' : 'app--app'; ?>">
    <?php if(!$is_auth): ?>
        <div class="app-shell" id="appShell">
            <?php if(file_exists(__DIR__ . '/sidebar.php')) { include 'sidebar.php'; } ?>
            <div class="app-overlay" id="appOverlay"></div>
            <div class="app-main">
                <header class="app-topbar">
                    <div class="topbar-left">
                        <button class="btn btn-icon" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                            <i class="bi bi-list"></i>
                        </button>
                        <div class="topbar-title">
                            <div class="topbar-title-text">
                                <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?>
                            </div>
                            <div class="topbar-subtitle">MailTrack System</div>
                        </div>
                    </div>
                    <div class="topbar-right">
                        <div class="topbar-search d-none d-md-flex">
                            <i class="bi bi-search"></i>
                            <input class="form-control" type="text" placeholder="Search emails, contacts..." aria-label="Search">
                        </div>
                        <div class="topbar-actions">
                            <button class="btn btn-icon btn-ghost" type="button" aria-label="Notifications">
                                <i class="bi bi-bell"></i>
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-icon btn-ghost dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account">
                                    <i class="bi bi-person-circle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <li class="dropdown-header">
                                            <div class="fw-semibold">Signed in</div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="../pages/profile.php">
                                                <i class="bi bi-gear me-2"></i>Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <a class="dropdown-item" href="../auth/login.php">Login</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="../auth/register.php">Register</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>
                <main class="app-content">
                    <div class="container-fluid">
    <?php else: ?>
        <main class="auth-main min-vh-100 d-flex align-items-center py-4">
            <div class="container w-100">
    <?php endif; ?>
