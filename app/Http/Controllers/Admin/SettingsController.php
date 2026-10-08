<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Ai\AiAssistant;
use App\Services\Ai\AiException;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit(BackupService $backups)
    {
        return view('admin.settings.edit', [
            'themes' => config('bufete.themes'),
            'backups' => $backups->list(),
            'hasGeminiKey' => filled(setting('gemini_api_key')),
            'hasOpenaiKey' => filled(setting('openai_api_key')),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'firm_name' => ['required', 'string', 'max:255'],
            'firm_slogan' => ['nullable', 'string', 'max:255'],
            'firm_tax_id' => ['nullable', 'string', 'max:60'],
            'firm_address' => ['nullable', 'string', 'max:255'],
            'firm_city' => ['nullable', 'string', 'max:120'],
            'firm_phone' => ['nullable', 'string', 'max:60'],
            'firm_email' => ['nullable', 'email', 'max:255'],
            'firm_website' => ['nullable', 'string', 'max:255'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'currency_decimals' => ['required', 'integer', 'in:0,2'],
            'number_format' => ['required', 'in:es,en'],
            'currency_words' => ['required', 'string', 'max:30'],
            'currency_suffix' => ['nullable', 'string', 'max:20'],
            'receipt_prefix' => ['required', 'string', 'max:10', 'alpha_dash'],
            'receipt_footer' => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::putMany($data);

        return redirect()->to(route('admin.settings.edit').'#general')->with('success', 'Datos del bufete guardados.');
    }

    public function updateAppearance(Request $request)
    {
        $request->validate([
            'theme_color' => ['required', Rule::in(array_keys(config('bufete.themes')))],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        Setting::put('theme_color', $request->input('theme_color'));

        if ($request->boolean('remove_logo') || $request->hasFile('logo')) {
            if ($old = setting('logo_path')) {
                Storage::disk('local')->delete($old);
            }
            Setting::put('logo_path', null);
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->storeAs('branding', 'logo-'.Str::random(8).'.'.strtolower($file->extension()), 'local');
            Setting::put('logo_path', $path);
        }

        return redirect()->to(route('admin.settings.edit').'#apariencia')->with('success', 'Apariencia actualizada.');
    }

    public function updateAi(Request $request)
    {
        $data = $request->validate([
            'ai_provider' => ['required', 'in:gemini,openai,none'],
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
            'gemini_model' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\-\/]+$/'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'openai_model' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\-\/]+$/'],
            'openai_base_url' => ['nullable', 'url', 'max:255'],
            'ai_system_prompt' => ['nullable', 'string', 'max:8000'],
        ]);

        // Las claves sólo se reemplazan si se escribe una nueva (o se pide borrarlas).
        foreach (['gemini_api_key', 'openai_api_key'] as $key) {
            if ($request->boolean('clear_'.$key)) {
                Setting::put($key, null);
            } elseif (filled($data[$key] ?? null)) {
                Setting::put($key, trim($data[$key]));
            }
            unset($data[$key]);
        }

        $data['ai_include_context'] = $request->boolean('ai_include_context') ? '1' : '0';
        $data['gemini_model'] = ($data['gemini_model'] ?? null) ?: config('bufete.ai.default_gemini_model');
        $data['openai_model'] = ($data['openai_model'] ?? null) ?: config('bufete.ai.default_openai_model');
        $data['openai_base_url'] = ($data['openai_base_url'] ?? null) ?: config('bufete.ai.default_openai_base_url');
        $data['ai_system_prompt'] = ($data['ai_system_prompt'] ?? null) ?: config('bufete.ai.default_system_prompt');

        Setting::putMany($data);

        return redirect()->to(route('admin.settings.edit').'#ia')->with('success', 'Configuración de IA guardada.');
    }

    public function testAi(Request $request, AiAssistant $assistant)
    {
        $provider = AiAssistant::provider();

        if ($provider === 'local') {
            return redirect()->to(route('admin.settings.edit').'#ia')
                ->with('error', 'No hay una clave de API configurada para el proveedor seleccionado.');
        }

        try {
            $answer = $assistant->client($provider)->chat('Responde de forma muy breve en español.', [
                ['role' => 'user', 'content' => 'Di "Conexión correcta" y el nombre del modelo que eres.'],
            ]);
        } catch (AiException $e) {
            return redirect()->to(route('admin.settings.edit').'#ia')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->to(route('admin.settings.edit').'#ia')->with('error', 'Error de conexión: '.$e->getMessage());
        }

        return redirect()->to(route('admin.settings.edit').'#ia')
            ->with('success', AiAssistant::providerLabel($provider).' respondió: '.Str::limit(strip_tags($answer), 200));
    }
}
