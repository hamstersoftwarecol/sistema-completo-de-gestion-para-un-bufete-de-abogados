<?php

namespace Tests;

use App\Enums\Priority;
use App\Models\CaseStatus;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // La configuración se guarda en memoria por petición: se limpia entre pruebas.
        Setting::flush();
    }

    protected function createClient(?User $owner = null, array $attributes = []): Client
    {
        static $n = 0;
        $n++;

        return Client::create($attributes + [
            'type' => 'persona',
            'name' => "Cliente de prueba {$n}",
            'document_type' => 'CC',
            'document_number' => '1000'.$n.random_int(1000, 9999),
            'email' => "cliente{$n}@example.com",
            'phone' => '+57 300 000 '.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'user_id' => $owner?->id,
        ]);
    }

    protected function createCase(User $lawyer, ?User $assistant = null, array $attributes = []): LegalCase
    {
        static $n = 0;
        $n++;

        $status = CaseStatus::firstOrCreate(['name' => 'Abierto'], ['color' => 'blue', 'is_closed' => false, 'sort_order' => 1]);
        $type = CaseType::firstOrCreate(['name' => 'Civil'], ['is_active' => true]);

        return LegalCase::create($attributes + [
            'case_number' => 'CASO-TEST-'.$n.'-'.random_int(1000, 9999),
            'title' => "Caso de prueba {$n}",
            'client_id' => $this->createClient($lawyer)->id,
            'case_type_id' => $type->id,
            'case_status_id' => $status->id,
            'lawyer_id' => $lawyer->id,
            'assistant_id' => $assistant?->id,
            'priority' => Priority::Medium,
            'fee_amount' => 1000000,
        ]);
    }
}
