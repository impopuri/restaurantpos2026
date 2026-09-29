<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('superadmin.accounts', ['users' => User::orderBy('role')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'role' => ['required', Rule::in(['cashier', 'superadmin'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['email'] = Str::uuid().'@accounts.etivacsilog.invalid';
        User::create($data);

        return redirect()->route('superadmin.accounts')->with('status', 'Account created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', Rule::in(['cashier', 'superadmin'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->role === 'superadmin' && $data['role'] === 'cashier' && User::where('role', 'superadmin')->count() <= 1) {
            return back()->withErrors(['role' => 'At least one superadmin account must remain.']);
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('superadmin.accounts')->with('status', 'Account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['account' => 'You cannot delete the account you are currently using.']);
        }

        if ($user->role === 'superadmin' && User::where('role', 'superadmin')->count() <= 1) {
            return back()->withErrors(['account' => 'At least one superadmin account must remain.']);
        }

        $user->delete();

        return redirect()->route('superadmin.accounts')->with('status', 'Account removed.');
    }
}