<?php

// Create admin user script for deployment

require_once 'vendor/autoload.php';

use App\Models\User;

echo "Creating admin user...\n";

try {
    $user = new User();
    $user->name = 'Admin';
    $user->email = 'admin@ppm.mpptrade.pl';  
    $user->password = password_hash('admin123', PASSWORD_DEFAULT);
    $user->save();
    
    echo "Admin user created successfully with ID: " . $user->id . "\n";
} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
}