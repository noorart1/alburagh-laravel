<?php

namespace App\Console\Commands;

use App\Models\BookCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportBookCatalog extends Command
{
    protected $signature = 'book-catalog:import
                            {file : Full path to the XLSX/XLS/CSV file}
                            {--commit : Actually write changes to database}
                            {--update : Update an existing record when barcode + title already exist}';

    protected $description = 'Safely import the independent Book Catalog from Excel/CSV. Dry-run by default.';

    private array $headerMap = [
        'باركد الكتاب' => 'barcode',
        'باركود الكتاب' => 'barcode',
        'اسم السلسلة' => 'series_name',
        'اسم الكتاب' => 'title',
        'اسم المؤلف' => 'author',
        'الرسام' => 'illustrator',
        'موضوع الكتاب' => 'subject',
        'رقم الطبعة' => 'edition_number',
        'مكان الطباعة' => 'print_place',
        'تاريخ الطباعة' => 'print_year',
        'سنة الطباعة' => 'print_year',
        'نبذة مختصرة عن الكتاب' => 'short_description',
        'عدد الصفحات' => 'pages',
        'السعر بالدولار' => 'price_usd',
        'بالدرهم الاماراتي' => 'price_aed',
        'بالدرهم الإماراتي' => 'price_aed',
        'بالدينار ع.' => 'price_iqd',
        'بالدينار العراقي' => 'price_iqd',
        'رقم الايداع الصحيح' => 'deposit_number',
        'رقم الإيداع الصحيح' => 'deposit_number',
        'رقم الايداع الخطأ' => 'wrong_deposit_number',
        'رقم الإيداع الخطأ' => 'wrong_deposit_number',
        'سنة الإيداع' => 'deposit_year',
        'سنة الايداع' => 'deposit_year',
        'القياس (سنتيمتر)' => 'size',
        'الوزن (كيلوغرام)' => 'weight',
        'الفئة العمرية' => 'age_group',
    ];

    public function handle(): int
    {
        $path = $this->argument('file');
        $commit = (bool) $this->option('commit');
        $update = (bool) $this->option('update');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        if (! $commit) {
            $this->warn('DRY RUN: no database changes will be made.');
            $this->line('After checking the report, run again with --commit.');
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $e) {
            $this->error('Cannot read spreadsheet: '.$e->getMessage());
            return self::FAILURE;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        $headerRow = $this->findHeaderRow($sheet, $highestRow, $highestColumn);

        if ($headerRow === null) {
            $this->error('Header row not found.');
            return self::FAILURE;
        }

        $columns = $this->buildColumnMap($sheet, $headerRow, $highestColumn);

        if (! isset($columns['title'])) {
            $this->error('Required column "اسم الكتاب" was not found.');
            return self::FAILURE;
        }

        // Ignore Excel formatting far below the real data.
        $lastMeaningfulRow = $this->findLastMeaningfulRow(
            $sheet,
            $headerRow + 1,
            $highestRow,
            $columns
        );

        $this->info("Header row: {$headerRow}");
        $this->info("Last meaningful row: {$lastMeaningfulRow}");
        $this->line('Detected fields: '.implode(', ', array_keys($columns)));

        $created = 0;
        $updated = 0;
        $exactDuplicates = 0;
        $sectionHeadings = 0;
        $blankRows = 0;
        $errors = 0;
        $repeatedBarcodes = 0;

        // Track barcode reuse for reporting only.
        // IMPORTANT: repeated barcode does NOT mean the record is skipped.
        $seenBarcodes = [];

        $process = function () use (
            $sheet,
            $headerRow,
            $lastMeaningfulRow,
            $columns,
            $commit,
            $update,
            &$created,
            &$updated,
            &$exactDuplicates,
            &$sectionHeadings,
            &$blankRows,
            &$errors,
            &$repeatedBarcodes,
            &$seenBarcodes
        ): void {
            for ($row = $headerRow + 1; $row <= $lastMeaningfulRow; $row++) {
                try {
                    $data = $this->readRow($sheet, $row, $columns);

                    if ($this->isEmptyRow($data)) {
                        $blankRows++;
                        continue;
                    }

                    $data = $this->cleanData($data);

                    // Rows such as "ألعاب البراق", "كتب", etc. are section headings,
                    // not catalog records.
                    if (empty($data['title'])) {
                        $sectionHeadings++;
                        continue;
                    }

                    $barcode = $data['barcode'] ?? null;

                    if ($barcode) {
                        if (isset($seenBarcodes[$barcode])) {
                            $repeatedBarcodes++;
                            $this->line(
                                "Row {$row}: barcode {$barcode} is used by more than one title; record preserved."
                            );
                        }

                        $seenBarcodes[$barcode] = true;
                    }

                    // A record is considered the same only when BOTH barcode and title match.
                    // This preserves historical rows where the same barcode appears on different titles.
                    $existing = $this->findExactExisting($data);

                    if ($existing) {
                        if (! $update) {
                            $exactDuplicates++;
                            continue;
                        }

                        if ($commit) {
                            $existing->fill($data);
                            $existing->save();
                        }

                        $updated++;
                        continue;
                    }

                    if ($commit) {
                        BookCatalog::create($data);
                    }

                    $created++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->error("Row {$row}: ".$e->getMessage());
                }
            }
        };

        if ($commit) {
            DB::transaction($process);
        } else {
            $process();
        }

        $this->newLine();

        $this->table(
            ['Result', 'Count'],
            [
                ['New records', $created],
                ['Updated records', $updated],
                ['Exact duplicates skipped', $exactDuplicates],
                ['Repeated barcodes preserved', $repeatedBarcodes],
                ['Section headings skipped', $sectionHeadings],
                ['Blank rows skipped', $blankRows],
                ['Errors', $errors],
            ]
        );

        if (! $commit) {
            $this->warn('Nothing was written. This was only a dry run.');
        } else {
            $this->info('Import completed successfully.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function findHeaderRow($sheet, int $highestRow, string $highestColumn): ?int
    {
        $limit = min($highestRow, 20);

        for ($row = 1; $row <= $limit; $row++) {
            $values = $sheet->rangeToArray(
                "A{$row}:{$highestColumn}{$row}",
                null,
                true,
                true,
                true
            )[$row];

            foreach ($values as $value) {
                $header = $this->normalizeHeader($value);

                if (in_array($header, ['باركد الكتاب', 'باركود الكتاب', 'اسم الكتاب'], true)) {
                    return $row;
                }
            }
        }

        return null;
    }

    private function buildColumnMap($sheet, int $headerRow, string $highestColumn): array
    {
        $row = $sheet->rangeToArray(
            "A{$headerRow}:{$highestColumn}{$headerRow}",
            null,
            true,
            true,
            true
        )[$headerRow];

        $columns = [];

        foreach ($row as $columnLetter => $headerValue) {
            $header = $this->normalizeHeader($headerValue);

            if ($header !== '' && isset($this->headerMap[$header])) {
                $columns[$this->headerMap[$header]] = $columnLetter;
            }
        }

        return $columns;
    }

    private function findLastMeaningfulRow(
        $sheet,
        int $startRow,
        int $highestRow,
        array $columns
    ): int {
        for ($row = $highestRow; $row >= $startRow; $row--) {
            foreach ($columns as $columnLetter) {
                $value = $sheet->getCell("{$columnLetter}{$row}")->getValue();

                if ($value !== null && trim((string) $value) !== '') {
                    return $row;
                }
            }
        }

        return $startRow - 1;
    }

    private function readRow($sheet, int $row, array $columns): array
    {
        $data = [];

        foreach ($columns as $field => $columnLetter) {
            $data[$field] = $sheet->getCell("{$columnLetter}{$row}")->getValue();
        }

        return $data;
    }

    private function cleanData(array $data): array
    {
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '') {
                $value = null;
            }

            $data[$field] = $value;
        }

        if (array_key_exists('barcode', $data)) {
            $data['barcode'] = $this->normalizeBarcode($data['barcode']);
        }

        foreach (['edition_number', 'print_year', 'pages', 'deposit_year'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->nullableInteger($data[$field]);
            }
        }

        foreach (['price_usd', 'price_aed', 'price_iqd', 'weight'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->nullableNumber($data[$field]);
            }
        }

        return $data;
    }

    private function findExactExisting(array $data): ?BookCatalog
    {
        $query = BookCatalog::query()
            ->where('title', $data['title']);

        if (! empty($data['barcode'])) {
            $query->where('barcode', $data['barcode']);
        } else {
            $query->whereNull('barcode');
        }

        return $query->first();
    }

    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeader($value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim((string) $value);
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    private function normalizeBarcode($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return number_format($value, 0, '.', '');
        }

        $value = trim((string) $value);

        if (preg_match('/^[0-9.]+[Ee][+-]?[0-9]+$/', $value)) {
            return number_format((float) $value, 0, '.', '');
        }

        return $value;
    }

    private function nullableInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $digits = preg_replace('/[^\d\-]/u', '', (string) $value);

        return ($digits === '' || $digits === '-') ? null : (int) $digits;
    }

    private function nullableNumber($value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([',', ' '], '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
