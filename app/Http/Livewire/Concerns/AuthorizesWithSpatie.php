<?php
namespace App\Http\Livewire\Concerns;

trait AuthorizesWithSpatie
{
    public array $userPermissions = [];
    public bool $isReadOnly = false;

    protected function getPermissionModule(): string
    {
        return 'products';
    }

    protected function getModuleActions(): array
    {
        return ['read', 'create', 'update', 'delete', 'export', 'import'];
    }

    protected function getExtraPermissions(): array
    {
        return [];
    }

    protected function initializePermissions(): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->isReadOnly = true;
            return;
        }
        $module = $this->getPermissionModule();
        foreach ($this->getModuleActions() as $action) {
            $permName = "{$module}.{$action}";
            try {
                $this->userPermissions[$action] = $user->hasPermissionTo($permName);
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                $this->userPermissions[$action] = false;
            }
        }
        foreach ($this->getExtraPermissions() as $key => $permName) {
            try {
                $this->userPermissions[$key] = $user->hasPermissionTo($permName);
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                $this->userPermissions[$key] = false;
            }
        }

        $this->isReadOnly = !($this->userPermissions['update'] ?? false)
                         && !($this->userPermissions['create'] ?? false);
    }

    public function userCan(string $action): bool
    {
        return $this->userPermissions[$action] ?? false;
    }

    protected function authorizeAction(string $action): void
    {
        if (!$this->userCan($action)) {
            abort(403, "Brak uprawnien: {$this->getPermissionModule()}.{$action}");
        }
    }
}
