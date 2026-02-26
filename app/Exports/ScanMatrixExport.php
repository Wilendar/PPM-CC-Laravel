<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Export macierzy cross-source skanowania produktow do CSV/XLSX.
 *
 * @param array $matrixData Flat array produktow z resolved cell statuses
 * @param array $sources    Array definicji zrodel [{key, name}, ...]
 */
class ScanMatrixExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected array $matrixData;
    protected array $sources;

    public function __construct(array $matrixData, array $sources)
    {
        $this->matrixData = $matrixData;
        $this->sources = $sources;
    }

    /**
     * Zwraca dane wierszy do eksportu.
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->matrixData as $product) {
            $row = [
                $product['sku'] ?? '',
                $product['name'] ?? '',
                $product['brand'] ?? '',
            ];

            foreach ($this->sources as $source) {
                $key = $source['key'] ?? '';
                $status = $product['cells'][$key] ?? '';
                $row[] = $this->getStatusLabel($status);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Zwraca naglowki kolumn (dynamiczne wg zrodel).
     */
    public function headings(): array
    {
        $base = ['SKU', 'Nazwa', 'Marka'];

        $sourceNames = array_map(
            fn($source) => $source['name'] ?? $source['key'] ?? '',
            $this->sources
        );

        return array_merge($base, $sourceNames);
    }

    /**
     * Nazwa arkusza w pliku XLSX.
     */
    public function title(): string
    {
        return 'Macierz Produktow';
    }

    /**
     * Style arkusza: bold + ciemne tlo naglowkow.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF374151'],
                ],
            ],
        ];
    }

    /**
     * Zdarzenia arkusza: auto-width kolumn po generowaniu.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestCol = $sheet->getHighestColumn();

                foreach (range('A', $highestCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }

    /**
     * Mapuje status komorki na polska etykiete tekstowa.
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'linked'            => 'Powiazany',
            'missing'           => 'Brak',
            'pending_sync'      => 'Oczekuje sync',
            'conflict'          => 'Konflikt',
            'brand_not_allowed' => 'Marka niedozwolona',
            default             => $status,
        };
    }
}
