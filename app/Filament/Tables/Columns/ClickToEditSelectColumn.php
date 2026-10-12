<?php

namespace App\Filament\Tables\Columns;

use BackedEnum;
use Filament\Tables\Columns\SelectColumn;

/**
 * Plain-text SelectColumn, see ClickToEditTextColumn. Options are fetched
 * once per column on first click through Filament's own getOptionsForJs().
 */
class ClickToEditSelectColumn extends SelectColumn
{
    public function toEmbeddedHtml(): string
    {
        $state = $this->getState();
        $value = (string) ($state instanceof BackedEnum ? $state->value : $state);
        $label = (string) ($this->getOptions()[$value] ?? $value);

        return '<div class="bc-edit" tabindex="0" data-edit="select"'
            .' data-name="'.e($this->getName()).'"'
            .' data-record="'.e($this->getRecordKey()).'"'
            .' data-nullable="'.($this->canSelectPlaceholder() ? '1' : '0').'"'
            .' data-value="'.e($value).'"'
            .' title="'.e($label).'">'
            .'<span class="bc-edit-val">'.e($label).'</span></div>';
    }
}
