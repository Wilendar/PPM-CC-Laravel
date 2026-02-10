<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected ?array $userIds;

    public function __construct(?array $userIds = null)
    {
        $this->userIds = $userIds;
    }

    public function collection()
    {
        $query = User::with('roles');

        if ($this->userIds && count($this->userIds) > 0) {
            $query->whereIn('id', $this->userIds);
        }

        return $query->orderBy('id')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Imie i nazwisko',
            'Email',
            'Firma',
            'Rola',
            'Status',
            'Ostatnie logowanie',
            'Data utworzenia',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->full_name,
            $user->email,
            $user->company ?? '',
            $user->roles->pluck('name')->implode(', '),
            $user->is_active ? 'Aktywny' : 'Nieaktywny',
            $user->last_login_at?->format('Y-m-d H:i'),
            $user->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
