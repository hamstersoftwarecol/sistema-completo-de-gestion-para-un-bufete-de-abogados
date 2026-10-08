<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(25),
        ]);
    }

    /** Marca como leída y abre el enlace de la notificación. */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? '');

        // Sólo se permiten redirecciones internas (rutas relativas o del propio sitio).
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return redirect()->to(url($url));
        }

        if ($url !== '' && str_starts_with($url, url('/').'/')) {
            return redirect()->to($url);
        }

        return redirect()->route('notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }

    public function clear(Request $request)
    {
        $request->user()->readNotifications()->delete();

        return back()->with('success', 'Notificaciones leídas eliminadas.');
    }
}
