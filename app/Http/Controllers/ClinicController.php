<?php

namespace App\Http\Controllers;

use App\Enums\ClinicType;
use App\Enums\Emirate;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClinicController extends Controller
{
    /** Super Admin → Clinics list. */
    public function index(): View
    {
        $clinics = Clinic::query()
            ->with('users.roles')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('super.clinics', [
            'clinics'     => $clinics,
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'super/clinics',
        ]);
    }

    public function create(): View
    {
        return $this->form(new Clinic(['is_active' => true]));
    }

    public function store(StoreClinicRequest $request): RedirectResponse
    {
        $clinic = Clinic::create($request->validated());
        $clinic->users()->sync($request->input('staff', []));

        return redirect()->route('super.clinics.index')
            ->with('status', __('pc.clinic_created'));
    }

    public function edit(Clinic $clinic): View
    {
        return $this->form($clinic);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic): RedirectResponse
    {
        $clinic->update($request->validated());
        $clinic->users()->sync($request->input('staff', []));

        return redirect()->route('super.clinics.index')
            ->with('status', __('pc.clinic_updated'));
    }

    /** Deactivate instead of deleting (per Phase 1a spec). */
    public function deactivate(Clinic $clinic): RedirectResponse
    {
        $clinic->update(['is_active' => false]);

        return redirect()->route('super.clinics.index')
            ->with('status', __('pc.clinic_deactivated'));
    }

    public function activate(Clinic $clinic): RedirectResponse
    {
        $clinic->update(['is_active' => true]);

        return redirect()->route('super.clinics.index')
            ->with('status', __('pc.clinic_activated'));
    }

    /** Shared create/edit form data. */
    private function form(Clinic $clinic): View
    {
        return view('super.clinic-form', [
            'clinic'      => $clinic,
            'types'       => ClinicType::options(),
            'emirates'    => Emirate::options(),
            'staff'       => User::role(['doctor', 'nurse', 'mammographer'])->orderBy('name')->get(),
            'assigned'    => $clinic->exists ? $clinic->users()->pluck('users.id')->all() : [],
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'super/clinics',
        ]);
    }
}
