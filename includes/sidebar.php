<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="<?php echo isset($_SESSION['user_id']) ? '../pages/dashboard.php' : '../index.php'; ?>">
            <span class="brand-icon">
                <i class="bi bi-envelope-check"></i>
            </span>
            <span class="brand-text">MailTrack</span>
        </a>
        <button class="btn btn-icon sidebar-close d-lg-none" id="sidebarClose" type="button" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label">Main</div>
        <ul class="nav flex-column sidebar-nav">
            <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($active_page) && $active_page === 'dashboard') ? 'active' : ''; ?>" href="../pages/dashboard.php">
                        <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($active_page) && $active_page === 'emails') ? 'active' : ''; ?>" href="../pages/emails.php">
                        <span class="nav-icon"><i class="bi bi-envelope"></i></span>
                        <span class="nav-label">Emails</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($active_page) && $active_page === 'profile') ? 'active' : ''; ?>" href="../pages/profile.php">
                        <span class="nav-icon"><i class="bi bi-person-circle"></i></span>
                        <span class="nav-label">Profile</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="../auth/login.php">
                        <span class="nav-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                        <span class="nav-label">Login</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../auth/register.php">
                        <span class="nav-icon"><i class="bi bi-person-plus"></i></span>
                        <span class="nav-label">Register</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="sidebar-section">
            <div class="sidebar-label">Account</div>
            <ul class="nav flex-column sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">
                        <span class="nav-icon text-warning"><i class="bi bi-box-arrow-right"></i></span>
                        <span class="nav-label">Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="mini-profile">
                <div class="avatar">MT</div>
                <div class="profile-meta">
                    <div class="profile-title">Signed in</div>
                    <div class="profile-subtitle"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</aside>
