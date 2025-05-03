<?php
// File: views/layout/admin_header.php (Example Addition)

// Assuming some standard admin header setup...
// require_once __DIR__ . '/../../includes/auth.php'; // May already be included
// if (!isAdmin()) { /* Redirect or error */ }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= $pageTitle ?? 'The Scent' ?></title>
    <!-- Include Tailwind, FontAwesome, Custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/style.css"> <!-- Link to your main CSS -->
    <link rel="stylesheet" href="/css/admin_style.css"> <!-- Optional: Admin-specific CSS -->
</head>
<body class="bg-gray-100 <?= $bodyClass ?? '' ?>">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="text-xl font-bold text-primary">The Scent - Admin</div>
            <div class="flex space-x-6">
                <a href="index.php?page=admin&section=dashboard" class="text-gray-600 hover:text-primary">Dashboard</a>
                <!-- START: Added Products Link -->
                <a href="index.php?page=admin&section=products" class="text-gray-600 hover:text-primary">Products</a>
                <!-- END: Added Products Link -->
                <a href="index.php?page=admin&section=orders" class="text-gray-600 hover:text-primary">Orders</a> <!-- Assuming Orders section exists -->
                <a href="index.php?page=admin&section=users" class="text-gray-600 hover:text-primary">Users</a> <!-- Assuming Users section exists -->
                <a href="index.php?page=admin&section=coupons" class="text-gray-600 hover:text-primary">Coupons</a>
                <a href="index.php?page=admin&section=quiz_analytics" class="text-gray-600 hover:text-primary">Quiz Analytics</a>
                <a href="index.php?page=logout" class="text-gray-600 hover:text-red-600"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
            </div>
        </nav>
    </header>
    <main> <!-- Start main content area -->
