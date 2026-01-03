<?php
session_start();

// Redirect based on login status
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
