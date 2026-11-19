<?php

// app/Models/Concerns/HasLocalizedColumns.php

namespace App\Models\Concerns;

/**
 * Mengambil kolom dwibahasa (mis. description_id / description_en) sesuai locale aktif (FR-05).
 */
trait HasLocalizedColumns
{
    public function localized(string $column): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return $this->{"{$column}_{$locale}"} ?: $this->{"{$column}_{$fallback}"};
    }
}
