<?php

namespace App\Models;

use App\Enums\ClinicType;
use App\Enums\Emirate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'type', 'emirate', 'address',
        'daily_capacity', 'contact_person', 'contact_phone', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'           => ClinicType::class,
            'emirate'        => Emirate::class,
            'is_active'      => 'boolean',
            'daily_capacity' => 'integer',
        ];
    }

    /** Staff (doctors, nurses, admins) assigned to this clinic. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function records(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientHistoryRecord::class);
    }

    public function patients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /** Short staff summary like "2D · 3N · 1M" (doctors / nurses / mammographers) for the clinics table. */
    public function staffSummary(): string
    {
        $users    = $this->relationLoaded('users') ? $this->users : $this->users()->with('roles')->get();
        $doctors  = $users->filter(fn (User $u) => $u->hasRole('doctor'))->count();
        $nurses   = $users->filter(fn (User $u) => $u->hasRole('nurse'))->count();
        $mammos   = $users->filter(fn (User $u) => $u->hasRole('mammographer'))->count();

        if ($doctors === 0 && $nurses === 0 && $mammos === 0) {
            return '—';
        }

        $parts = ["{$doctors}D", "{$nurses}N"];
        if ($mammos > 0) {
            $parts[] = "{$mammos}M";
        }

        return implode(' · ', $parts);
    }
}
