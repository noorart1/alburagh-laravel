<?php

$path = __DIR__ . '/app/Filament/Resources/BookCatalogs/Tables/BookCatalogsTable.php';

if (! is_file($path)) {
    fwrite(STDERR, "ERROR: Run this file from the Laravel project root.\n");
    exit(1);
}

$code = file_get_contents($path);
$backup = $path . '.bak-' . date('Ymd-His');
copy($path, $backup);

// 1) Add Builder import.
if (! str_contains($code, 'use Illuminate\\Database\\Eloquent\\Builder;')) {
    $anchor = "use Filament\\Tables\\Table;\n";
    if (! str_contains($code, $anchor)) {
        fwrite(STDERR, "ERROR: Could not find Table import. Nothing changed.\n");
        exit(1);
    }
    $code = str_replace(
        $anchor,
        $anchor . "use Illuminate\\Database\\Eloquent\\Builder;\n",
        $code
    );
}

// 2) Make # show temporary value when present.
$oldColumn = <<<'PHP'
                TextColumn::make('catalog_number')
                    ->width('74px')
                    ->label('#')
                    ->searchable()
                    ->copyable(),
PHP;

$newColumn = <<<'PHP'
                TextColumn::make('catalog_number')
                    ->width('74px')
                    ->label('#')
                    ->formatStateUsing(
                        fn ($state, BookCatalog $record): string =>
                            filled($record->temporary_catalog_number)
                                ? (string) $record->temporary_catalog_number
                                : (string) $state
                    )
                    ->searchable()
                    ->copyable(),
PHP;

if (str_contains($code, $oldColumn)) {
    $code = str_replace($oldColumn, $newColumn, $code);
} elseif (! str_contains($code, 'temporary_catalog_number')) {
    fwrite(STDERR, "ERROR: Could not find catalog_number column block. Nothing changed.\n");
    exit(1);
}

// 3) Sort 1010, 1010+, 1011 ... while keeping the real catalog_number unique.
$oldSort = "            ->defaultSort('catalog_number', 'asc')";

$newSort = <<<'PHP'
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query
                    ->orderByRaw("
                        CASE
                            WHEN temporary_catalog_number IS NULL
                              OR temporary_catalog_number = ''
                            THEN catalog_number
                            ELSE CAST(temporary_catalog_number AS UNSIGNED)
                        END ASC
                    ")
                    ->orderByRaw("
                        CASE
                            WHEN temporary_catalog_number IS NULL
                              OR temporary_catalog_number = ''
                            THEN 0
                            ELSE 1
                        END ASC
                    ")
                    ->orderBy('catalog_number', 'asc');
            })
PHP;

if (str_contains($code, $oldSort)) {
    $code = str_replace($oldSort, $newSort, $code);
} elseif (! str_contains($code, 'CAST(temporary_catalog_number AS UNSIGNED)')) {
    fwrite(STDERR, "ERROR: Could not find defaultSort line. Nothing changed.\n");
    exit(1);
}

// Persisted user sort can override our temporary placement, so remove only this line.
$code = str_replace("\n            ->persistSortInSession()", "", $code);

file_put_contents($path, $code);

echo "OK\n";
echo "Patched: {$path}\n";
echo "Backup:  {$backup}\n";
