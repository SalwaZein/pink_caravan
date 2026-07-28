<?php

namespace App\Enums;

enum Emirate: string
{
    case AbuDhabi     = 'abu_dhabi';
    case Dubai        = 'dubai';
    case Sharjah      = 'sharjah';
    case Ajman        = 'ajman';
    case UmmAlQuwain  = 'umm_al_quwain';
    case RasAlKhaimah = 'ras_al_khaimah';
    case Fujairah     = 'fujairah';

    /** Human-friendly label (translatable). */
    public function label(): string
    {
        return match ($this) {
            self::AbuDhabi     => __('pc.em_abu_dhabi'),
            self::Dubai        => __('pc.em_dubai'),
            self::Sharjah      => __('pc.em_sharjah'),
            self::Ajman        => __('pc.em_ajman'),
            self::UmmAlQuwain  => __('pc.em_umm_al_quwain'),
            self::RasAlKhaimah => __('pc.em_ras_al_khaimah'),
            self::Fujairah     => __('pc.em_fujairah'),
        };
    }

    /** [value => label] for select inputs. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $e) => [$e->value => $e->label()])
            ->all();
    }
}
