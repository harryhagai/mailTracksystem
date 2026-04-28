<?php
require_once 'includes/security.php';
if (isset($_SESSION['user_id'])) {
    redirect_to("pages/dashboard.php");
} else {
    redirect_to("auth/login.php");
}
?>
