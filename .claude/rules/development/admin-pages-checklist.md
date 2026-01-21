# Development: Admin Pages & Routes Checklist (MANDATORY)

## Critical Rule
**EVERY** new admin page MUST follow this checklist before deployment!

## 1. Livewire Component - Layout Configuration

**MANDATORY:** Admin pages use `layouts.admin`, NOT `layouts.app`!

```php
// CORRECT - Admin pages
public function render()
{
    return view('livewire.admin.my-component')
        ->layout('layouts.admin', [
            'title' => 'Page Title - Admin PPM',
            'breadcrumb' => 'Page Title'
        ]);
}

// WRONG - Will show without sidebar!
public function render()
{
    return view('livewire.admin.my-component')
        ->layout('layouts.app');  // NO! Missing admin sidebar!
}
```

## 2. Blade View - Dark Theme Requirements

**All admin views MUST use dark theme:**

| Element | Correct Classes | Wrong Classes |
|---------|-----------------|---------------|
| Background | `bg-gray-900`, `bg-gray-800` | `bg-white`, `bg-gray-100` |
| Cards | `bg-gray-800/50`, `border-gray-700` | `bg-white`, `border-gray-200` |
| Text | `text-white`, `text-gray-300` | `text-gray-900`, `text-gray-700` |
| Active tabs | `bg-[#e0ac7e]` (orange brand) | `bg-blue-600` |
| Inputs | `bg-gray-700 border-gray-600 text-white` | `bg-white border-gray-300` |

## 3. Route Registration

```php
// routes/web.php - Inside admin middleware group
Route::prefix('admin')->name('admin.')->middleware([...])->group(function () {
    // CORRECT - Full page Livewire component
    Route::get('/my-page', \App\Http\Livewire\Admin\MyComponent::class)
        ->name('my-page.index');
});
```

## 4. Sidebar Navigation Links

Add links in BOTH files:
- `resources/views/layouts/navigation.blade.php` (mobile/responsive)
- `resources/views/layouts/admin.blade.php` (desktop sidebar)

## 5. Pre-Deployment Checklist

Before deploying ANY new admin page:

- [ ] Component uses `->layout('layouts.admin', [...])`
- [ ] Blade view uses dark theme (bg-gray-900, bg-gray-800)
- [ ] Active states use orange brand color (#e0ac7e)
- [ ] Route registered in admin middleware group
- [ ] Sidebar links added to both navigation files
- [ ] All required Livewire components uploaded to production
- [ ] All required Blade views uploaded to production
- [ ] `php artisan view:clear && cache:clear` executed
- [ ] Chrome DevTools verification shows sidebar + dark theme

## 6. Common Mistakes

### Missing Sidebar
**Symptom:** Page loads but no left sidebar
**Cause:** Using `layouts.app` instead of `layouts.admin`
**Fix:** Change to `->layout('layouts.admin', [...])`

### Light Theme Instead of Dark
**Symptom:** White/light backgrounds on admin page
**Cause:** Blade uses `bg-white`, `bg-gray-100` classes
**Fix:** Replace with `bg-gray-900`, `bg-gray-800`

### Blue Active States
**Symptom:** Tabs/buttons use blue instead of orange
**Cause:** Using Tailwind default `bg-blue-600`
**Fix:** Use brand color `bg-[#e0ac7e]` or `var(--mpp-primary)`

### Route Not Working
**Symptom:** 404 or wrong component loaded
**Cause:** Route outside admin middleware group or wrong class path
**Fix:** Verify route is inside `Route::prefix('admin')` group

## 7. File Upload Checklist for Production

When deploying new admin pages:

```powershell
# 1. Upload Livewire component
pscp ... "app/Http/Livewire/Admin/MyComponent.php" -> production

# 2. Upload Blade view
pscp ... "resources/views/livewire/admin/my-component.blade.php" -> production

# 3. Upload routes (if changed)
pscp ... "routes/web.php" -> production

# 4. Upload navigation (if changed)
pscp ... "resources/views/layouts/navigation.blade.php" -> production
pscp ... "resources/views/layouts/admin.blade.php" -> production

# 5. Clear caches
plink ... "php artisan view:clear && cache:clear && route:clear"

# 6. MANDATORY: Chrome DevTools verification!
```

## 8. Reference: Working Admin Pages

Copy patterns from these working pages:
- `/admin/sessions` - Sessions.php
- `/admin/erp-manager` - ERPManager.php
- `/admin/backup` - BackupManager.php
- `/admin/system-settings` - SystemSettings.php
