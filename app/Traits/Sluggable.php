<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Sluggable
{
    public function generateSlug(string $value, string $field = 'slug'): string
    {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where($field, $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
