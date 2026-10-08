<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::putMany([
            'firm_name' => 'Mejía & Asociados Abogados',
            'firm_slogan' => 'Asesoría jurídica integral',
            'firm_tax_id' => '901.234.567-8',
            'firm_address' => 'Calle 72 # 10-34, Oficina 801',
            'firm_city' => 'Bogotá D.C., Colombia',
            'firm_phone' => '+57 601 345 6789',
            'firm_email' => 'contacto@mejiaasociados.test',
            'firm_website' => 'www.mejiaasociados.test',
            'currency_symbol' => '$',
            'currency_decimals' => '0',
            'number_format' => 'es',
            'currency_words' => 'PESOS',
            'currency_suffix' => 'M/CTE',
            'receipt_prefix' => 'REC',
            'receipt_footer' => 'Gracias por su confianza. Este recibo no constituye factura electrónica de venta.',
            'theme_color' => 'indigo',
            'ai_provider' => 'gemini',
            'gemini_model' => config('bufete.ai.default_gemini_model'),
            'openai_model' => config('bufete.ai.default_openai_model'),
            'openai_base_url' => config('bufete.ai.default_openai_base_url'),
            'ai_system_prompt' => config('bufete.ai.default_system_prompt'),
            'ai_include_context' => '1',
        ]);

        // Permite precargar claves de IA desde .env (opcional).
        if (filled(env('GEMINI_API_KEY'))) {
            Setting::put('gemini_api_key', env('GEMINI_API_KEY'));
        }
        if (filled(env('OPENAI_API_KEY'))) {
            Setting::put('openai_api_key', env('OPENAI_API_KEY'));
            if (blank(env('GEMINI_API_KEY'))) {
                Setting::put('ai_provider', 'openai');
            }
        }
    }
}
