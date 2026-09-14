<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Business feedback round 3 (Sep 2026).
     *
     * The mammography session becomes a short "Mammography Screening" form —
     * patient details, a manually entered PC number and one free-text findings
     * box — which the mammographer submits and assigns to a RADIOLOGIST. The
     * radiologist is a new role with a single "Patient Report" tab: it shows what
     * the mammographer filed and takes the final PDF report from the radiology
     * system, which the radiologist sends straight to the patient.
     */
    private const PERMISSIONS = ['manage_radiology'];

    private const GRANTS = [
        'super_admin' => ['manage_radiology'],
    ];

    public function up(): void
    {
        // One free-text findings box replaces the per-side / BI-RADS grid.
        Schema::table('mammogram_findings', function (Blueprint $table) {
            $table->text('findings')->nullable()->after('mammographer_id');
        });

        // The radiologist side of a case: who it is with, and the final report.
        Schema::table('patient_history_records', function (Blueprint $table) {
            $table->foreignId('radiologist_id')->nullable()->after('mammographer_id')
                ->constrained('users')->nullOnDelete();
            $table->string('radiology_report_path')->nullable()->after('report_sent_at');
            $table->timestamp('radiology_report_uploaded_at')->nullable()->after('radiology_report_path');
            $table->timestamp('radiology_report_sent_at')->nullable()->after('radiology_report_uploaded_at');
        });

        Role::findOrCreate('radiologist', 'web');

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // The radiologist role exists only to report on mammography studies.
        User::role('radiologist')->get()->each(fn (User $u) => $u->givePermissionTo('manage_radiology'));

        // Backfill existing users. A fresh migrate-then-seed gets these from
        // Rbac::ROLE_DEFAULTS instead, hence the role-exists guard.
        foreach (self::GRANTS as $role => $permissions) {
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                continue;
            }

            User::role($role)->get()->each(fn (User $u) => $u->givePermissionTo($permissions));
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GRANTS as $role => $permissions) {
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                continue;
            }

            User::role($role)->get()->each(fn (User $u) => $u->revokePermissionTo($permissions));
        }

        Schema::table('patient_history_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('radiologist_id');
            $table->dropColumn(['radiology_report_path', 'radiology_report_uploaded_at', 'radiology_report_sent_at']);
        });

        Schema::table('mammogram_findings', function (Blueprint $table) {
            $table->dropColumn('findings');
        });
    }
};
