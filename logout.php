<?php
session_start();
require 'config/session.php';

session_destroy();
clearRememberMeCookies();

// Redirect to home
header("Location: index.php");
exit;
?>
