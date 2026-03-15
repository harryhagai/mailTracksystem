<?php $is_auth = strpos($_SERVER['REQUEST_URI'], 'auth') !== false; ?>
            </div>
        <?php if(!$is_auth): ?>
                </main>

                <!-- Footer -->
                <footer class="app-footer">
                    <div class="container-fluid">
                        <div class="footer-content">
                            <div class="footer-left">
                                <span class="fw-semibold">MailTrack</span>
                                <span class="text-muted">Email follow-up made simple</span>
                            </div>
                            <div class="footer-right">
                                <span class="text-muted">&copy; <?php echo date('Y'); ?> MailTrack System</span>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        <?php else: ?>
            </main>
            <footer class="auth-footer">
                <div class="container">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> MailTrack System. All rights reserved.</p>
                </div>
            </footer>
        <?php endif; ?>

        <!-- Bootstrap JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Custom Scripts -->
        <script src="<?php echo $base_url ?? '../'; ?>assets/js/main.js"></script>
    </body>
</html>
