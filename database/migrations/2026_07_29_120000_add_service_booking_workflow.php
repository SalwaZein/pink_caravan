<?php

use App\Support\Rbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** Service-booking review workflow: audit-trail columns + the four action permissions. */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Review / approve
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('approved_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');

            // Payment
            $table->foreignId('paid_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('paid_by');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('paid_at');
            $table->string('payment_reference')->nullable()->after('payment_amount');

            // Completion
            $table->foreignId('completed_by')->nullable()->after('payment_reference')->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
            $table->string('completion_report_path')->nullable()->after('completed_at');
            $table->text('completion_notes')->nullable()->after('completion_report_path');
        });

        // Ensure the new booking permissions exist and grant them to existing super admins,
        // so the workflow is usable on databases that were seeded before this migration.
        // On a fresh migrate-then-seed DB the roles don't exist yet — the seeders handle
        // that path — so only backfill when the super_admin role is already present.
        foreach (Rbac::GROUPS['service_bookings'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            \App\Models\User::role('super_admin')->get()
                ->each(fn ($user) => $user->givePermissionTo(Rbac::GROUPS['service_bookings']));
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn([
                'reviewed_at', 'approved_at', 'rejection_reason', 'rejected_at',
                'paid_at', 'payment_amount', 'payment_reference',
                'completed_at', 'completion_report_path', 'completion_notes',
            ]);
        });
    }
};
