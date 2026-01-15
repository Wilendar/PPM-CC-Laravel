<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

/**
 * XLSX Template Generator Service
 *
 * Generates downloadable XLSX templates for import wizards.
 * Templates are customized based on ProductType with:
 * - Header row with instructions
 * - Column validation rules
 * - Sample data rows
 * - Formatted styling
 *
 * @package App\Services\Import
 * @version 1.0
 * @since ETAP_08 - Import/Export System
 */
class XlsxTemplateGenerator
{
    /**
     * Template definitions per product type
     */
    private const TEMPLATES = [
        'pojazdy' => [
            'name' => 'POJAZDY - Szablon Importu',
            'columns' => [
                'SKU' => ['required' => true, 'example' => 'MOTO-001', 'type' => 'text'],
                'Nazwa' => ['required' => true, 'example' => 'Yamaha YCF 88', 'type' => 'text'],
                'Typ Produktu' => ['required' => true, 'example' => 'pojazd', 'type' => 'list', 'values' => ['pojazd']],
                'Kategoria L3' => ['required' => true, 'example' => 'Pojazdy', 'type' => 'text'],
                'Kategoria L4' => ['required' => false, 'example' => 'Motorowery', 'type' => 'text'],
                'Kategoria L5' => ['required' => false, 'example' => 'YCF', 'type' => 'text'],
                'Cena' => ['required' => true, 'example' => '15999.99', 'type' => 'numeric'],
                'Stan' => ['required' => false, 'example' => '5', 'type' => 'numeric'],
                'Waga (kg)' => ['required' => false, 'example' => '75.5', 'type' => 'numeric'],
                'Model' => ['required' => false, 'example' => 'YCF 88', 'type' => 'text'],
                'Rok' => ['required' => false, 'example' => '2024', 'type' => 'numeric'],
                'Silnik' => ['required' => false, 'example' => '88cc 4T', 'type' => 'text'],
                'VIN' => ['required' => false, 'example' => 'VIN12345678', 'type' => 'text'],
            ],
        ],
        'części' => [
            'name' => 'CZĘŚCI ZAMIENNE - Szablon Importu',
            'columns' => [
                'SKU' => ['required' => true, 'example' => 'PART-001', 'type' => 'text'],
                'Nazwa' => ['required' => true, 'example' => 'Klocki hamulcowe', 'type' => 'text'],
                'Typ Produktu' => ['required' => true, 'example' => 'część_zamienna', 'type' => 'list', 'values' => ['część_zamienna']],
                'Kategoria L3' => ['required' => true, 'example' => 'Części', 'type' => 'text'],
                'Kategoria L4' => ['required' => false, 'example' => 'Układ hamulcowy', 'type' => 'text'],
                'Kategoria L5' => ['required' => false, 'example' => 'Klocki', 'type' => 'text'],
                'Cena' => ['required' => true, 'example' => '89.99', 'type' => 'numeric'],
                'Stan' => ['required' => false, 'example' => '50', 'type' => 'numeric'],
                'Waga (kg)' => ['required' => false, 'example' => '0.5', 'type' => 'numeric'],
                'Producent' => ['required' => false, 'example' => 'OEM Parts', 'type' => 'text'],
                'Kod Producenta' => ['required' => false, 'example' => 'MFR-12345', 'type' => 'text'],
                'Dopasowania Oryginał' => ['required' => false, 'example' => 'YCF 50|YCF 88', 'type' => 'text'],
                'Dopasowania Zamiennik' => ['required' => false, 'example' => 'Honda CRF50', 'type' => 'text'],
            ],
        ],
        'odzież' => [
            'name' => 'ODZIEŻ - Szablon Importu',
            'columns' => [
                'SKU' => ['required' => true, 'example' => 'APPAREL-001', 'type' => 'text'],
                'Nazwa' => ['required' => true, 'example' => 'Koszulka MX', 'type' => 'text'],
                'Typ Produktu' => ['required' => true, 'example' => 'odzież', 'type' => 'list', 'values' => ['odzież']],
                'Kategoria L3' => ['required' => true, 'example' => 'Odzież', 'type' => 'text'],
                'Kategoria L4' => ['required' => false, 'example' => 'Koszulki', 'type' => 'text'],
                'Cena' => ['required' => true, 'example' => '149.99', 'type' => 'numeric'],
                'Stan' => ['required' => false, 'example' => '100', 'type' => 'numeric'],
                'Waga (kg)' => ['required' => false, 'example' => '0.3', 'type' => 'numeric'],
                'Ma Warianty?' => ['required' => false, 'example' => 'TAK', 'type' => 'list', 'values' => ['TAK', 'NIE']],
                'Wariant - Kolor' => ['required' => false, 'example' => 'Czerwony', 'type' => 'text'],
                'Wariant - Rozmiar' => ['required' => false, 'example' => 'L', 'type' => 'list', 'values' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']],
                'Wariant - SKU Suffix' => ['required' => false, 'example' => '-RED-L', 'type' => 'text'],
            ],
        ],
        'ogólne' => [
            'name' => 'OGÓLNE - Szablon Importu',
            'columns' => [
                'SKU' => ['required' => true, 'example' => 'PROD-001', 'type' => 'text'],
                'Nazwa' => ['required' => true, 'example' => 'Nazwa produktu', 'type' => 'text'],
                'Typ Produktu' => ['required' => true, 'example' => 'ogólny', 'type' => 'list', 'values' => ['pojazd', 'część_zamienna', 'odzież', 'ogólny']],
                'Kategoria L3' => ['required' => true, 'example' => 'Kategoria główna', 'type' => 'text'],
                'Kategoria L4' => ['required' => false, 'example' => 'Podkategoria', 'type' => 'text'],
                'Cena' => ['required' => true, 'example' => '99.99', 'type' => 'numeric'],
                'Stan' => ['required' => false, 'example' => '10', 'type' => 'numeric'],
            ],
        ],
    ];

    /**
     * Generate XLSX template for specific product type
     *
     * @param string $productType 'pojazdy', 'części', 'odzież', 'ogólne'
     * @return string Path to generated file
     * @throws \Exception
     */
    public function generate(string $productType = 'ogólne'): string
    {
        $template = self::TEMPLATES[$productType] ?? self::TEMPLATES['ogólne'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($template['name'], 0, 31)); // Max 31 chars for sheet name

        // Set column headers
        $col = 1;
        foreach ($template['columns'] as $columnName => $config) {
            $cellCoord = $this->getColumnLetter($col) . '1';

            // Header text
            $headerText = $config['required'] ? "{$columnName} *" : $columnName;
            $sheet->setCellValue($cellCoord, $headerText);

            // Header styling (dark blue background, white text, bold)
            $sheet->getStyle($cellCoord)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $config['required'] ? '1F4788' : '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Column width auto-size
            $sheet->getColumnDimension($this->getColumnLetter($col))->setWidth(20);

            // Add sample data in row 2
            $sheet->setCellValue($this->getColumnLetter($col) . '2', $config['example']);

            // Add data validation for list types
            if ($config['type'] === 'list' && isset($config['values'])) {
                $this->addDataValidation($sheet, $col, $config['values']);
            }

            $col++;
        }

        // Instructions row (row 3)
        $sheet->setCellValue('A3', 'INSTRUKCJE:');
        $sheet->mergeCells('A3:C3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setItalic(true);

        $sheet->setCellValue('A4', '1. Kolumny oznaczone * są wymagane');
        $sheet->setCellValue('A5', '2. SKU musi być unikalny (3-50 znaków, bez polskich znaków)');
        $sheet->setCellValue('A6', '3. Dopasowania rozdziel znakiem | (np. "YCF 50|YCF 88")');
        $sheet->setCellValue('A7', '4. Usuń te instrukcje przed importem lub pozostaw (zostaną zignorowane)');

        // Save to temp file
        $filename = sprintf(
            'import_template_%s_%s.xlsx',
            $productType,
            date('Y-m-d_His')
        );

        $tempPath = storage_path('app/temp/' . $filename);

        // Ensure directory exists
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Add data validation (dropdown list) to a column
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $columnIndex
     * @param array $values
     */
    private function addDataValidation($sheet, int $columnIndex, array $values): void
    {
        $columnLetter = $this->getColumnLetter($columnIndex);

        // Apply to rows 2-1000 (practical limit)
        $range = "{$columnLetter}2:{$columnLetter}1000";

        $validation = $sheet->getCell("{$columnLetter}2")->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Nieprawidłowa wartość');
        $validation->setError('Wybierz wartość z listy');
        $validation->setPromptTitle('Dozwolone wartości');
        $validation->setPrompt(implode(', ', $values));
        $validation->setFormula1('"' . implode(',', $values) . '"');

        // Copy validation to all rows
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell("{$columnLetter}{$row}")->setDataValidation(clone $validation);
        }
    }

    /**
     * Convert column index to Excel column letter (1 = A, 2 = B, ..., 27 = AA)
     *
     * @param int $columnIndex
     * @return string
     */
    private function getColumnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)) . $letter;
            $columnIndex = (int) ($columnIndex / 26);
        }
        return $letter;
    }

    /**
     * Get available templates
     *
     * @return array
     */
    public function getAvailableTemplates(): array
    {
        return array_map(function ($key, $template) {
            return [
                'key' => $key,
                'name' => $template['name'],
                'column_count' => count($template['columns']),
            ];
        }, array_keys(self::TEMPLATES), self::TEMPLATES);
    }
}
