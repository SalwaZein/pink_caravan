<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Clinic;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /** Super Admin → Users & roles list. */
    public function index(): View
    {
        $users = User::query()->with(['roles', 'clinics'])->orderBy('name')->get();

        return view('super.users', [
            'users'       => $users,
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'super/users',
        ]);
    }

    public function create(): View
    {
        return $this->form(new User());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);
        $user->syncPermissions($data['permissions'] ?? []);
        $user->clinics()->sync($data['clinics'] ?? []);

        return redirect()->route('super.users.index')->with('status', __('pc.user_created'));
    }

    public function edit(User $user): View
    {
        return $this->form($user);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $user->syncRoles([$data['role']]);
        $user->syncPermissions($data['permissions'] ?? []);
        $user->clinics()->sync($data['clinics'] ?? []);

        return redirect()->route('super.users.index')->with('status', __('pc.user_updated'));
    }

    /** Shared create/edit form data. */
    private function form(User $user): View
    {
        $roles       = Role::orderBy('name')->pluck('name');
        $currentRole = $user->exists ? $user->getRoleNames()->first() : $roles->first();

        $selectedPerms = old(
            'permissions',
            $user->exists ? $user->getDirectPermissions()->pluck('name')->all() : Rbac::defaultsFor($currentRole),
        );

        return view('super.user-form', [
            'user'          => $user,
            'roles'         => $roles,
            'clinics'       => Clinic::orderBy('name')->get(),
            'currentRole'   => $currentRole,
            'assigned'      => $user->exists ? $user->clinics()->pluck('clinics.id')->all() : [],
            'permGroups'    => Rbac::GROUPS,
            'roleDefaults'  => Rbac::ROLE_DEFAULTS,
            'selectedPerms' => $selectedPerms,
            'sidebarRole'   => auth()->user()->sidebarRole(),
            'route'         => 'super/users',
        ]);
    }
}
