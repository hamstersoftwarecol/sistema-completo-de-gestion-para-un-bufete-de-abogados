<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Iniciar sesión como" otro abogado (sólo superadministrador).
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        $admin = $request->user();

        abort_unless($admin->isSuperadmin(), 403);

        if ($user->id === $admin->id) {
            return back()->with('error', 'Ya tiene la sesión iniciada con este usuario.');
        }

        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'Primero regrese a su cuenta de administrador.');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $admin->id);

        return redirect()->route('dashboard')->with('success', "Ahora está viendo el sistema como {$user->name}.");
    }

    public function leave(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');
        abort_unless($adminId, 403);

        $admin = User::findOrFail($adminId);
        Auth::guard('web')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.users.index')->with('success', 'Regresó a su cuenta de administrador.');
    }
}
