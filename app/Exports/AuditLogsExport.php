<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogsExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;
    protected array $exportFields;

    public function __construct(array $data, array $exportFields)
    {
        $this->data = $data;
        $this->exportFields = $exportFields;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        $headingLabels = [
            'created_at' => 'Data',
            'user_name' => 'Uzytkownik',
            'action' => 'Akcja',
            'model_type' => 'Model',
            'model_id' => 'ID Modelu',
            'ip_address' => 'Adres IP',
            'user_agent' => 'User Agent',
            'changes' => 'Zmiany',
            'old_values' => 'Stare wartosci',
            'new_values' => 'Nowe wartosci',
        ];

        $headers = [];
        foreach ($this->exportFields as $field => $enabled) {
            if ($enabled) {
                $headers[] = $headingLabels[$field] ?? $field;
            }
        }

        return $headers;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
