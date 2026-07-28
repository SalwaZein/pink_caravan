<?php

namespace App\Enums;

enum ClinicType: string
{
    case Fixed  = 'fixed';
    case Mobile = 'mobile';
    case Mini   = 'mini';

    /** Human-friendly label (translatable). */
    public function label(): string
    {
        return match ($this) {
            self::Fixed  => __('pc.fixed'),
            self::Mobile => __('pc.mobile_type'),
            self::Mini   => __('pc.mini'),
        };
    }

    /** Badge colours mirroring the design's typeMeta. */
    public function color(): string
    {
        return match ($this) {
            self::Fixed  => '#2A6FDB',
            self::Mobile => '#16A6A6',
            self::Mini   => '#F7941E',
        };
    }

    public function background(): string
    {
        return match ($this) {
            self::Fixed  => '#E3ECFB',
            self::Mobile => '#DBF1EE',
            self::Mini   => '#FCEBD6',
        };
    }

    /** [value => label] for select inputs. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
