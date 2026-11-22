<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Atomic counter updates that never go below zero. MySQL rejects a negative
 * value on an unsigned column, which surfaced as a 500 on unlike/dislike.
 */
class Counters
{
    public static function increment(Model $model, string $column): void
    {
        static::apply($model, $column, 1);
    }

    public static function decrement(Model $model, string $column): void
    {
        static::apply($model, $column, -1);
    }

    private static function apply(Model $model, string $column, int $delta): void
    {
        $expression = $delta >= 0
            ? '`'.$column.'` + '.$delta
            : 'GREATEST(0, `'.$column.'` - '.abs($delta).')';

        $model->newQuery()
            ->whereKey($model->getKey())
            ->update([$column => DB::raw($expression)]);

        $model->setAttribute($column, max(0, (int) $model->getAttribute($column) + $delta));
        $model->syncOriginalAttribute($column);
    }
}
