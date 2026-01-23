<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * Controller wrapper for UserForm Livewire component
 *
 * Workaround for Livewire 3 full-page component routing issues.
 * Routes to this controller which renders the Livewire component.
 */
class UserFormController extends Controller
{
    /**
     * Show create user form
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Show edit user form
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', ['user' => $user]);
    }
}
