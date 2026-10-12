<?php

namespace App\Filament\Tables\Columns;

use BackedEnum;
use Filament\Tables\Columns\TextInputColumn;

/**
 * Same as TextInputColumn on the server (rules, saving through
 * updateTableColumnState), but each cell renders as plain text instead of
 * an Alpine input component. One delegated script in
 * book-catalog-fullscreen.blade.php turns the clicked cell into an input,
 * so a wide page no longer boots hundreds of components on load.
 */
class ClickToEditTextColumn extends TextInputColumn
{
    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();
        $value = (string) ($state instanceof BackedEnum ? $state->value : $state);

        return '<div class="bc-edit" tabindex="0" data-edit="text"'
            .' data-name="'.e($this->getName()).'"'
            .' data-record="'.e($this->getRecordKey()).'"'
            .' data-type="'.e($this->getType()).'"'
            .' data-value="'.e($value).'"'
            .' title="'.e($value).'">'
            .'<span class="bc-edit-val">'.e($value).'</span></div>';
    }
}
