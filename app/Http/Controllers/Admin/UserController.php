<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->input('q').'%')
                ->orWhere('email', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->withCount(['casesAsLawyer', 'casesAsAssistant'])
            ->orderByRaw("CASE role WHEN 'superadmin' THEN 0 WHEN 'senior' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', ['user' => new User(['role' => Role::Junior, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = User::create($data);

        return redirect()->route('admin.users.index')->with('success', "Usuario {$user->name} creado.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->id === $request->user()->id && ($data['role'] !== Role::Superadmin->value || ! $data['is_active'])) {
            return back()->withInput()->with('error', 'No puede quitarse a sí mismo el rol de superadministrador ni desactivar su propia cuenta.');
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', "Usuario {$user->name} actualizado.");
    }

    public function toggle(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? "{$user->name} fue activado." : "{$user->name} fue desactivado y ya no podrá iniciar sesión.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puede eliminar su propia cuenta.');
        }

        $linked = array_filter([
            'casos como responsable' => $user->casesAsLawyer()->count(),
            'casos como asistente' => $user->casesAsAssistant()->count(),
            'clientes' => $user->clients()->count(),
        ]);

        if ($linked) {
            return back()->with('error', "No se puede eliminar a {$user->name} porque tiene "
                .collect($linked)->map(fn ($n, $k) => "{$n} {$k}")->implode(', ')
                .'. Reasígnelos o desactive el usuario.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'phone' => ['nullable', 'string', 'max:40'],
            'professional_id' => ['nullable', 'string', 'max:60'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
