<?php
require_once '../config/session.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
