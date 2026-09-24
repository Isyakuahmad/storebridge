<?php
if(session_status()!==PHP_SESSION_ACTIVE)session_start();require_once __DIR__.'/../config/database.php';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function go($u){header("Location: $u");exit;}
function csrf(){return $_SESSION['csrf']??($_SESSION['csrf']=bin2hex(random_bytes(32)));}
function csrf_check(){if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??''))exit('Invalid form token.');}
function login_required(){if(empty($_SESSION['user_id']))go('/auth/login.php');}
function is_admin(){global $pdo;if(empty($_SESSION['user_id']))return false;$q=$pdo->prepare("SELECT role,account_status FROM users WHERE id=?");$q->execute([$_SESSION['user_id']]);$u=$q->fetch();return $u&&$u['role']==='admin'&&$u['account_status']==='active';}
function admin_required(){login_required();if(!is_admin())go('/seller/dashboard.php');}
function store(){global $pdo;$q=$pdo->prepare('SELECT * FROM stores WHERE user_id=?');$q->execute([$_SESSION['user_id']??0]);return $q->fetch();}
function money($v){return '₦'.number_format((float)$v,2);}
function cart_count(){return array_sum($_SESSION['cart']??[]);}
