<?php
require_once '../core/Session.php';
Session::destroy();
header('Location: login-modern.php');
exit;
?>