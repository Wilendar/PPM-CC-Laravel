<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Livewire\Dashboard\AdminDashboard;

class AdminLoginTest extends Command
{
    protected $signature = 'test:admin-login {email=admin@mpptrade.pl}';
    protected $description = 'Test admin login and dashboard functionality';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Testing admin login for: {$email}");
        
        // Find user
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User {$email} not found");
            return 1;
        }
        
        $this->info("✅ User found: {$user->name}");
        
        // Check if admin
        if (!$user->hasRole('Admin')) {
            $this->error("User {$email} is not an admin");
            return 1;
        }
        
        $this->info("✅ User has Admin role");
        
        // Simulate login
        Auth::login($user);
        
        $this->info("✅ User logged in successfully");
        
        // Test AdminDashboard
        try {
            $dashboard = new AdminDashboard();
            $dashboard->mount();
            
            $this->info("✅ AdminDashboard mounted successfully");
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Products', $dashboard->dashboardStats['total_products'] ?? 0],
                    ['Users', $dashboard->dashboardStats['active_users'] ?? 0],
                    ['Categories', $dashboard->dashboardStats['total_categories'] ?? 0],
                    ['Activity (24h)', $dashboard->dashboardStats['recent_activity'] ?? 0],
                ]
            );
            
            $this->info("✅ Admin Panel fully functional!");
            
        } catch (\Exception $e) {
            $this->error("Dashboard error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}