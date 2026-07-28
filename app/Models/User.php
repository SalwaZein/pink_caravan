<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** Clinics this staff member is assigned to (many-to-many). */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class);
    }

    /** IDs of the clinics this user is assigned to. */
    public function clinicIds(): array
    {
        return $this->clinics()->pluck('clinics.id')->all();
    }

    /** Records where this user is the assigned doctor. */
    public function assignedRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientHistoryRecord::class, 'assigned_doctor_id');
    }

    /** The primary role name for this user (nurse|doctor|clinic_admin|super_admin), or null. */
    public function primaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }

    /** Which sidebar key ('nurse'|'doctor'|'clinic'|'super') this user maps to. */
    public function sidebarRole(): string
    {
        return match ($this->primaryRole()) {
            'super_admin'  => 'super',
            'clinic_admin' => 'clinic',
            'doctor'       => 'doctor',
            'mammographer' => 'mammographer',
            default        => 'nurse',
        };
    }

    /** Landing route name for this user's role after login. */
    public function homeRoute(): string
    {
        return match ($this->primaryRole()) {
            'super_admin'  => 'super.dashboard',
            'clinic_admin' => 'clinic.queue',
            'doctor'       => 'doctor.assigned',
            'mammographer' => 'mammographer.queue',
            default        => 'nurse.queue',
        };
    }

    /** Two-letter initials for the avatar (drops an honorific like "Dr."). */
    public function initials(): string
    {
        $name = str_replace('Dr. ', '', $this->name);

        $initials = \Illuminate\Support\Str::of($name)
            ->explode(' ')
            ->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))
            ->join('');

        return \Illuminate\Support\Str::substr($initials, 0, 2);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
