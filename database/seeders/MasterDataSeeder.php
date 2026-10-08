<?php

namespace Database\Seeders;

use App\Models\CaseStatus;
use App\Models\CaseType;
use App\Models\Court;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Civil', 'Procesos declarativos, ejecutivos y de responsabilidad civil'],
            ['Penal', 'Defensa y representación de víctimas'],
            ['Laboral', 'Conflictos individuales y colectivos de trabajo'],
            ['Familia', 'Divorcios, custodia, alimentos y sucesiones'],
            ['Comercial', 'Sociedades, contratos mercantiles e insolvencia'],
            ['Administrativo', 'Medios de control contra entidades públicas'],
            ['Tributario', 'Discusiones con la autoridad tributaria'],
            ['Constitucional', 'Acciones de tutela, populares y de grupo'],
        ];
        foreach ($types as [$name, $description]) {
            CaseType::create(['name' => $name, 'description' => $description, 'is_active' => true]);
        }

        $statuses = [
            ['Abierto', 'blue', false],
            ['En trámite', 'indigo', false],
            ['Etapa probatoria', 'violet', false],
            ['En apelación', 'amber', false],
            ['Suspendido', 'orange', false],
            ['Ganado', 'green', true],
            ['Conciliado', 'teal', true],
            ['Perdido', 'red', true],
            ['Archivado', 'gray', true],
        ];
        foreach ($statuses as $i => [$name, $color, $closed]) {
            CaseStatus::create(['name' => $name, 'color' => $color, 'is_closed' => $closed, 'sort_order' => $i + 1]);
        }

        $courts = [
            ['Juzgado 5 Civil del Circuito', 'Bogotá', 'Carrera 10 # 14-33, piso 4', '+57 601 282 0000'],
            ['Juzgado 15 Civil Municipal', 'Bogotá', 'Calle 14 # 7-36', '+57 601 341 0000'],
            ['Juzgado 12 Penal Municipal con Función de Control de Garantías', 'Bogotá', 'Complejo Judicial de Paloquemao', '+57 601 375 0000'],
            ['Juzgado 3 Laboral del Circuito', 'Medellín', 'Carrera 52 # 42-73, Edificio José Félix de Restrepo', '+57 604 232 0000'],
            ['Juzgado 2 de Familia', 'Bogotá', 'Calle 12C # 7-36', '+57 601 286 0000'],
            ['Tribunal Superior — Sala Civil', 'Bogotá', 'Avenida Calle 24 # 53-28', '+57 601 423 3390'],
            ['Juzgado 7 Administrativo', 'Cali', 'Carrera 4 # 12-04', '+57 602 898 0000'],
            ['Superintendencia de Sociedades', 'Bogotá', 'Avenida El Dorado # 51-80', '+57 601 220 1000'],
        ];
        foreach ($courts as [$name, $city, $address, $phone]) {
            Court::create(compact('name', 'city', 'address', 'phone'));
        }
    }
}
