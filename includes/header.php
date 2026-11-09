<?php require_once __DIR__.'/auth.php';?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0d4f34">
<title><?=e($title??'Choosery')?></title>
<link rel="icon" type="image/svg+xml" href="/assets/logo-mark.svg?v=2">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/app.css?v=1" rel="stylesheet">
</head>
<body class="storebridge-texture">
<nav class="navbar navbar-expand-lg navbar-dark storebridge-navbar mb-4">
  <div class="container">
    <a class="navbar-brand storebridge-brand d-flex align-items-center" href="/">
      <img class="storebridge-brand-mark me-2" src="/assets/logo-mark.svg?v=2" width="32" height="32" alt="" aria-hidden="true" decoding="async">Choosery
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#storebridgeNavigation" aria-controls="storebridgeNavigation" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="storebridgeNavigation">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="/#how-it-works">How it works</a></li>
        <li class="nav-item"><a class="nav-link" href="/public/products.php">Explore products</a></li>
        <li class="nav-item"><a class="nav-link" href="/public/sellers.php">Explore sellers</a></li>
        <li class="nav-item"><a class="nav-link" href="/contact.php">Contact</a></li>
        <?php if(!empty($_SESSION['user_id'])):?>
          <li class="nav-item"><a class="nav-link" href="/seller/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="/seller/store.php">My store</a></li>
          <li class="nav-item"><a class="nav-link" href="/seller/products.php">Products</a></li>
          <li class="nav-item"><a class="nav-link" href="/seller/orders.php">Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="/seller/subscription.php">Plans &amp; payments</a></li>
          <li class="nav-item"><a class="nav-link" href="/seller/referrals.php">Referrals</a></li>
          <li class="nav-item"><a class="nav-link" href="/seller/account.php">Account</a></li>
          <?php if(is_admin()):?>
            <li class="nav-item"><a class="nav-link text-warning" href="/admin/">Admin</a></li>
            <li class="nav-item"><a class="nav-link text-warning" href="/admin/payments.php">Payments</a></li>
            <li class="nav-item"><a class="nav-link text-warning" href="/admin/plans.php">Plans</a></li>
            <li class="nav-item"><a class="nav-link text-warning" href="/admin/referrals.php">Referrals</a></li>
            <li class="nav-item"><a class="nav-link text-warning" href="/admin/registration-flags.php">Registration flags</a></li>
          <?php endif;?>
          <li class="nav-item ms-lg-2"><a class="btn btn-sm btn-light" href="/auth/logout.php">Log out</a></li>
        <?php else:?>
          <li class="nav-item"><a class="nav-link" href="/auth/login.php">Log in</a></li>
          <li class="nav-item ms-lg-2"><a class="btn btn-sm btn-light" href="/auth/register.php">Create a store</a></li>
        <?php endif;?>
      </ul>
    </div>
  </div>
</nav>
<main class="container pb-5 storebridge-content">
