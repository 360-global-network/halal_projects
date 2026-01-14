<?php
require_once '../config.php';
require_once '../admin_auth.php';

$auth = new AdminAuth();
$auth->logout();

header('Location: login.php');
exit();
?>