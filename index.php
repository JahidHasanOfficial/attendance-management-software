<?php
require_once 'includes/auth.php';
redirectIfLoggedIn();
header("Location: login");
exit();
?>
