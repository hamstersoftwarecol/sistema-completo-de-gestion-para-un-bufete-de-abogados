<?php

namespace Database\Seeders;

use App\Enums\AppointmentMode;
use App\Enums\AppointmentStatus;
use App\Enums\ClientType;
use App\Enums\CommunicationType;
use App\Enums\ExpenseStatus;
use App\Enums\HearingStatus;
use App\Enums\PaymentMethod;
use App\Enums\Priority;
use App\Enums\Role;
use App\Models\AiConversation;
use App\Models\Appointment;
use App\Models\CaseStatus;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Court;
use App\Models\Expense;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\Message;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Ai\LocalAssistant;
use App\Services\SimplePdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Datos de demostración realistas para presentar el sistema.
 */
class DemoSeeder extends Seeder
{
    private array $receiptCounters = [];

    public function run(): void
    {
        mt_srand(2026);

        // ------------------------------------------------------------------
        // Usuarios (contraseña: password)
        // ------------------------------------------------------------------
        $admin = User::create([
            'name' => 'Carlos Andrés Mejía', 'email' => 'admin@bufete.test', 'password' => 'password',
            'role' => Role::Superadmin, 'phone' => '+57 310 555 0101', 'professional_id' => 'T.P. 98.765',
            'specialty' => 'Socio director — Derecho comercial', 'email_verified_at' => now(),
        ]);
        $laura = User::create([
            'name' => 'Laura Valentina Gómez', 'email' => 'senior@bufete.test', 'password' => 'password',
            'role' => Role::Senior, 'phone' => '+57 311 555 0202', 'professional_id' => 'T.P. 154.321',
            'specialty' => 'Derecho civil y de familia', 'email_verified_at' => now(),
        ]);
        $jorge = User::create([
            'name' => 'Jorge Enrique Ramírez', 'email' => 'senior2@bufete.test', 'password' => 'password',
            'role' => Role::Senior, 'phone' => '+57 312 555 0303', 'professional_id' => 'T.P. 120.456',
            'specialty' => 'Derecho penal y laboral', 'email_verified_at' => now(),
        ]);
        $daniela = User::create([
            'name' => 'Daniela Torres Restrepo', 'email' => 'junior@bufete.test', 'password' => 'password',
            'role' => Role::Junior, 'phone' => '+57 313 555 0404', 'professional_id' => 'T.P. 301.987',
            'specialty' => 'Litigio civil', 'email_verified_at' => now(),
        ]);
        $santiago = User::create([
            'name' => 'Santiago Herrera Ruiz', 'email' => 'junior2@bufete.test', 'password' => 'password',
            'role' => Role::Junior, 'phone' => '+57 314 555 0505', 'professional_id' => 'T.P. 315.246',
            'specialty' => 'Derecho laboral', 'email_verified_at' => now(),
        ]);

        // ------------------------------------------------------------------
        // Clientes
        // ------------------------------------------------------------------
        $clientRows = [
            [ClientType::Person, 'María Fernanda López Castro', 'CC', '52.345.678', 'Bogotá', 'Docente', $laura],
            [ClientType::Company, 'Constructora Andina S.A.S.', 'NIT', '900.123.456-1', 'Bogotá', 'Construcción', $admin, 'Ing. Ricardo Peña (Gerente)'],
            [ClientType::Person, 'Juan Pablo Martínez Ríos', 'CC', '79.876.543', 'Medellín', 'Comerciante', $jorge],
            [ClientType::Person, 'Ana Lucía Moreno Vargas', 'CC', '1.020.345.678', 'Bogotá', 'Enfermera', $laura],
            [ClientType::Company, 'Transportes del Valle Ltda.', 'NIT', '805.987.654-3', 'Cali', 'Transporte de carga', $admin, 'Dra. Patricia Cifuentes (Representante legal)'],
            [ClientType::Person, 'Pedro Antonio Suárez Gil', 'CC', '80.123.987', 'Bogotá', 'Conductor', $jorge],
            [ClientType::Person, 'Camila Andrea Ruiz Pardo', 'CC', '1.032.456.789', 'Bogotá', 'Diseñadora', $laura],
            [ClientType::Company, 'Café Montaña Verde S.A.S.', 'NIT', '901.456.789-0', 'Manizales', 'Exportación de café', $admin, 'Sr. Hernán Ocampo (Gerente)'],
            [ClientType::Person, 'Luis Eduardo Castaño Mejía', 'CC', '71.234.567', 'Medellín', 'Operario', $jorge],
            [ClientType::Person, 'Gloria Inés Patiño de Rojas', 'CC', '41.567.890', 'Bogotá', 'Pensionada', $laura],
            [ClientType::Company, 'Inversiones El Roble S.A.', 'NIT', '860.111.222-5', 'Bogotá', 'Inmobiliaria', $admin, 'Dr. Felipe Arango (Apoderado general)'],
            [ClientType::Person, 'Andrés Felipe Quintero Lara', 'CC', '1.144.567.123', 'Cali', 'Ingeniero de sistemas', $jorge],
            [ClientType::Person, 'Sofía Alejandra Beltrán Niño', 'CC', '1.015.678.901', 'Bogotá', 'Estudiante', $daniela],
            [ClientType::Company, 'Clínica Santa Teresa S.A.S.', 'NIT', '900.765.432-9', 'Bogotá', 'Salud', $admin, 'Dra. Mónica Salazar (Directora jurídica)'],
        ];

        $clients = [];
        foreach ($clientRows as $i => $row) {
            [$type, $name, $docType, $doc, $city, $occupation, $owner] = $row;
            $slug = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z]+/', '.')->trim('.')->limit(24, '');
            $clients[] = Client::create([
                'type' => $type,
                'name' => $name,
                'document_type' => $docType,
                'document_number' => $doc,
                'email' => $slug.'@example.com',
                'phone' => '+57 3'.mt_rand(0, 2).mt_rand(0, 9).' '.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                'alt_phone' => $type === ClientType::Company ? '+57 60'.mt_rand(1, 6).' '.mt_rand(200, 799).' '.mt_rand(1000, 9999) : null,
                'address' => ['Calle', 'Carrera', 'Avenida', 'Transversal'][$i % 4].' '.mt_rand(10, 150).' # '.mt_rand(5, 99).'-'.mt_rand(10, 99),
                'city' => $city,
                'occupation' => $occupation,
                'contact_person' => $row[7] ?? null,
                'notes' => $i % 3 === 0 ? 'Cliente referido. Prefiere comunicación por WhatsApp en horario de oficina.' : null,
                'user_id' => $owner->id,
                'created_at' => now()->subDays(mt_rand(60, 400)),
            ]);
        }

        // ------------------------------------------------------------------
        // Casos
        // ------------------------------------------------------------------
        $type = fn (string $name) => CaseType::where('name', $name)->value('id');
        $status = fn (string $name) => CaseStatus::where('name', $name)->value('id');
        $court = fn (int $n) => Court::query()->orderBy('id')->skip($n)->value('id');

        // [cliente, título, tipo, estado, juzgado, juez, responsable, asistente, prioridad, honorarios, días desde radicación]
        $caseRows = [
            [0, 'Divorcio contencioso y liquidación de sociedad conyugal', 'Familia', 'En trámite', 4, 'Dra. Claudia Patricia Vélez', $laura, $daniela, Priority::High, 12000000, 210],
            [1, 'Proceso ejecutivo — cobro de facturas de obra', 'Comercial', 'Etapa probatoria', 0, 'Dr. Hernando Castillo', $admin, $daniela, Priority::High, 35000000, 320],
            [2, 'Defensa penal por presunta estafa agravada', 'Penal', 'En trámite', 2, 'Dr. Mauricio Benítez', $jorge, $santiago, Priority::Urgent, 25000000, 150],
            [3, 'Custodia y fijación de cuota alimentaria', 'Familia', 'Abierto', 4, 'Dra. Claudia Patricia Vélez', $laura, null, Priority::Medium, 5500000, 45],
            [4, 'Responsabilidad civil extracontractual — accidente de tránsito', 'Civil', 'En apelación', 5, 'Sala Civil — M.P. Dr. Óscar Fernández', $admin, null, Priority::High, 40000000, 540],
            [5, 'Demanda laboral por despido sin justa causa', 'Laboral', 'Etapa probatoria', 3, 'Dra. Liliana Zapata', $jorge, $santiago, Priority::Medium, 8000000, 260],
            [6, 'Restitución de inmueble arrendado', 'Civil', 'Ganado', 1, 'Dr. Andrés Bermúdez', $laura, $daniela, Priority::Low, 4500000, 380],
            [7, 'Incumplimiento de contrato de suministro internacional', 'Comercial', 'Abierto', 7, null, $admin, $santiago, Priority::High, 60000000, 30],
            [8, 'Reconocimiento de pensión de invalidez', 'Laboral', 'En trámite', 3, 'Dra. Liliana Zapata', $jorge, null, Priority::Medium, 6000000, 190],
            [9, 'Sucesión intestada', 'Familia', 'En trámite', 4, 'Dr. Ernesto Galindo', $laura, $daniela, Priority::Medium, 9000000, 130],
            [10, 'Proceso de pertenencia por prescripción adquisitiva', 'Civil', 'Suspendido', 0, 'Dr. Hernando Castillo', $admin, null, Priority::Low, 18000000, 600],
            [11, 'Acción de tutela — protección de datos personales', 'Constitucional', 'Ganado', 1, 'Dr. Andrés Bermúdez', $jorge, $santiago, Priority::Urgent, 2500000, 75],
            [12, 'Reclamación por accidente de trabajo', 'Laboral', 'Abierto', 3, null, $jorge, $daniela, Priority::Medium, 7000000, 20],
            [13, 'Nulidad y restablecimiento del derecho — sanción de la Supersalud', 'Administrativo', 'En trámite', 6, 'Dr. Ramiro Ospina', $admin, $daniela, Priority::High, 45000000, 280],
            [1, 'Defensa ante liquidación oficial de impuestos', 'Tributario', 'Abierto', 6, null, $admin, null, Priority::Medium, 22000000, 15],
            [0, 'Proceso ejecutivo de alimentos', 'Familia', 'Conciliado', 4, 'Dra. Claudia Patricia Vélez', $laura, null, Priority::Low, 3000000, 420],
            [5, 'Denuncia por lesiones personales culposas', 'Penal', 'Archivado', 2, 'Dr. Mauricio Benítez', $jorge, null, Priority::Low, 4000000, 700],
            [3, 'Impugnación de paternidad', 'Familia', 'Etapa probatoria', 4, 'Dr. Ernesto Galindo', $laura, $daniela, Priority::Medium, 6500000, 160],
            [9, 'Responsabilidad médica por falla en el servicio', 'Civil', 'En trámite', 0, 'Dr. Hernando Castillo', $laura, null, Priority::High, 30000000, 240],
            [12, 'Querella por injuria y calumnia', 'Penal', 'Abierto', 2, null, $jorge, $santiago, Priority::Medium, 5000000, 10],
        ];

        $partyNames = ['Carlos Alberto Méndez', 'Rosa Elena Díaz', 'Banco Popular S.A.', 'Seguros La Previsora', 'Hernán Darío Cruz',
            'Marta Lucía Ospina', 'Fiscalía 23 Seccional', 'Inmobiliaria Bolívar', 'Diego Fernando Salas', 'Colpensiones',
            'EPS Salud Total', 'Nelson Javier Prieto', 'Comercializadora XYZ Ltda.', 'Paula Andrea Gil', 'Dr. Fabián Correa (perito)'];

        $noteTexts = [
            'Se radicó memorial solicitando la práctica de pruebas documentales y testimoniales.',
            'Cliente aportó copias de los soportes de pago. Pendiente digitalizar originales.',
            'Llamada con el apoderado de la contraparte: manifiesta interés en conciliar. Analizar propuesta.',
            'Revisar términos para presentar recurso de apelación (3 días hábiles desde la notificación).',
            'Se notificó por estado el auto que admite la demanda.',
            'Preparar interrogatorio de parte y lista de preguntas para testigos.',
            'El despacho fijó fecha para audiencia. Confirmar asistencia del cliente.',
            'Solicitar certificado de tradición y libertad actualizado.',
        ];

        $hearingTitles = ['Audiencia inicial', 'Audiencia de conciliación', 'Audiencia de pruebas', 'Audiencia de instrucción y juzgamiento',
            'Audiencia de alegatos', 'Lectura de fallo', 'Audiencia preliminar', 'Diligencia de inspección'];

        $cases = [];
        foreach ($caseRows as $i => $r) {
            [$ci, $title, $typeName, $statusName, $courtIdx, $judge, $lawyer, $assistant, $priority, $fee, $daysAgo] = $r;
            $filed = now()->subDays($daysAgo)->startOfDay();
            $year = $filed->format('Y');
            $statusId = $status($statusName);
            $closed = CaseStatus::find($statusId)->is_closed;

            $case = LegalCase::create([
                'case_number' => sprintf('11001-31-%02d-%03d-%s-%05d-00', mt_rand(1, 40), mt_rand(1, 50), $year, 100 + $i * 37),
                'title' => $title,
                'client_id' => $clients[$ci]->id,
                'case_type_id' => $type($typeName),
                'case_status_id' => $statusId,
                'court_id' => $court($courtIdx),
                'judge' => $judge,
                'lawyer_id' => $lawyer->id,
                'assistant_id' => $assistant?->id,
                'priority' => $priority,
                'filing_date' => $filed,
                'closed_at' => $closed ? now()->subDays(mt_rand(5, max(6, (int) ($daysAgo / 3)))) : null,
                'description' => "Asunto: {$title}. Cliente: {$clients[$ci]->name}. Estrategia: revisar antecedentes, recaudar material probatorio y preparar la teoría del caso. Se acordó informar al cliente cada 15 días sobre el avance del proceso.",
                'fee_amount' => $fee,
                'fee_notes' => $fee > 20000000 ? '30% al inicio, 30% en etapa probatoria y 40% al fallo' : 'Pago en cuotas mensuales',
                'created_at' => $filed,
                'updated_at' => now()->subDays(mt_rand(0, 20)),
            ]);
            $cases[] = $case;

            // Partes
            $roles = match ($typeName) {
                'Penal' => ['denunciante', 'denunciado', 'fiscal', 'testigo'],
                'Laboral' => ['demandante', 'demandado', 'testigo'],
                default => ['demandante', 'demandado', 'apoderado_contraparte', 'testigo', 'perito'],
            };
            foreach (array_slice($roles, 0, mt_rand(2, count($roles))) as $k => $role) {
                $isClientSide = $k === 0;
                $case->parties()->create([
                    'name' => $isClientSide ? $clients[$ci]->name : $partyNames[($i + $k * 3) % count($partyNames)],
                    'role' => $role,
                    'document_number' => $isClientSide ? $clients[$ci]->document_number : (string) mt_rand(10000000, 99999999),
                    'phone' => '+57 3'.mt_rand(10, 29).' '.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                    'email' => $isClientSide ? $clients[$ci]->email : null,
                    'lawyer_name' => $isClientSide ? $lawyer->name : ($role === 'demandado' ? 'Dr. '.['Ricardo Lozano', 'Adriana Mesa', 'Felipe Rueda'][mt_rand(0, 2)] : null),
                ]);
            }

            // Notas
            foreach (range(1, mt_rand(1, 3)) as $n) {
                $case->notes()->create([
                    'user_id' => ($n % 2 === 0 && $assistant) ? $assistant->id : $lawyer->id,
                    'body' => $noteTexts[($i + $n) % count($noteTexts)],
                    'is_pinned' => $n === 1 && $i % 4 === 0,
                    'created_at' => now()->subDays(mt_rand(1, max(2, $daysAgo))),
                ]);
            }

            // Audiencias: algunas realizadas y otras próximas
            if (! in_array($statusName, ['Archivado'], true)) {
                $past = mt_rand(0, 2);
                for ($h = 0; $h < $past; $h++) {
                    $date = now()->subDays(mt_rand(10, max(11, $daysAgo - 5)))->setTime([8, 9, 10, 14, 15][mt_rand(0, 4)], [0, 30][mt_rand(0, 1)]);
                    Hearing::create([
                        'legal_case_id' => $case->id, 'user_id' => $lawyer->id, 'court_id' => $case->court_id,
                        'title' => $hearingTitles[$h], 'hearing_type' => $hearingTitles[$h], 'scheduled_at' => $date,
                        'duration_minutes' => [60, 90, 120][mt_rand(0, 2)], 'location' => 'Sala '.mt_rand(1, 12).' — '.Court::find($case->court_id)?->name,
                        'judge' => $judge, 'status' => $h === 1 && mt_rand(0, 1) ? HearingStatus::Postponed : HearingStatus::Held,
                        'outcome' => 'Se evacuaron las pruebas decretadas. El despacho fijó nueva fecha para continuar la diligencia.',
                        'reminder_sent_at' => $date->copy()->subDay(),
                    ]);
                }

                if (! $closed) {
                    $days = [1, 2, 3, 5, 6, 8, 10, 13, 16, 20, 24, 28, 35, 42, 50][$i % 15];
                    $date = now()->addDays($days)->setTime([8, 9, 10, 11, 14, 15][$i % 6], [0, 30][$i % 2]);
                    Hearing::create([
                        'legal_case_id' => $case->id, 'user_id' => $i % 3 === 0 && $assistant ? $assistant->id : $lawyer->id,
                        'court_id' => $case->court_id, 'title' => $hearingTitles[($i + 2) % count($hearingTitles)],
                        'hearing_type' => $hearingTitles[($i + 2) % count($hearingTitles)], 'scheduled_at' => $date,
                        'duration_minutes' => 90, 'location' => $i % 4 === 0 ? 'Virtual — enlace Microsoft Teams enviado por el despacho' : 'Sala '.mt_rand(1, 12),
                        'judge' => $judge, 'status' => HearingStatus::Scheduled,
                        'notes' => 'Llevar originales y llegar 30 minutos antes. Confirmar asistencia del cliente y testigos.',
                    ]);
                }
            }

            // Pagos
            $nPayments = $fee > 0 ? mt_rand(0, 4) : 0;
            $paidSoFar = 0;
            for ($p = 0; $p < $nPayments; $p++) {
                $amount = round(($fee * [0.3, 0.2, 0.15, 0.1][$p]) / 50000) * 50000;
                if ($paidSoFar + $amount > $fee) {
                    break;
                }
                $paidSoFar += $amount;
                $this->payment($clients[$ci], $case, $lawyer, $amount,
                    now()->subDays(mt_rand(1, max(2, $daysAgo)))->startOfDay(),
                    $p === 0 ? 'Anticipo de honorarios' : 'Abono a honorarios — cuota '.($p + 1));
            }

            // Gastos
            foreach (range(0, mt_rand(0, 3)) as $e) {
                if ($e === 0) {
                    continue;
                }
                $categories = array_keys(config('bufete.expense_categories'));
                $category = $categories[($i + $e) % count($categories)];
                $submitter = $assistant && $e % 2 === 1 ? $assistant : $lawyer;
                $st = [ExpenseStatus::Pending, ExpenseStatus::Approved, ExpenseStatus::Reimbursed, ExpenseStatus::Rejected][($i + $e) % 4];
                $date = now()->subDays(mt_rand(1, 60));
                Expense::create([
                    'legal_case_id' => $case->id,
                    'user_id' => $submitter->id,
                    'category' => $category,
                    'description' => match ($category) {
                        'tasas' => 'Arancel judicial para radicación',
                        'notaria' => 'Autenticación de poder en notaría',
                        'copias' => 'Copias del expediente y certificaciones',
                        'transporte' => 'Transporte a diligencia en el despacho',
                        'mensajeria' => 'Envío de citación para notificación personal',
                        'peritos' => 'Anticipo honorarios perito avaluador',
                        'publicaciones' => 'Publicación de edicto emplazatorio',
                        default => 'Gastos varios del proceso',
                    },
                    'amount' => [35000, 48000, 75000, 120000, 250000, 600000][mt_rand(0, 5)],
                    'expense_date' => $date,
                    'billable' => $e !== 3,
                    'status' => $st,
                    'reviewed_by' => $st !== ExpenseStatus::Pending ? $admin->id : null,
                    'reviewed_at' => $st !== ExpenseStatus::Pending ? $date->copy()->addDay() : null,
                    'review_notes' => $st === ExpenseStatus::Rejected ? 'No se adjuntó soporte válido. Por favor anexe la factura.' : null,
                    'reimbursed_by' => $st === ExpenseStatus::Reimbursed ? $admin->id : null,
                    'reimbursed_at' => $st === ExpenseStatus::Reimbursed ? $date->copy()->addDays(3) : null,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            // Documentos de ejemplo (PDF generados)
            if ($i < 12) {
                $docs = [
                    ['Poder especial', 'poder', ["Yo, {$clients[$ci]->name}, identificado(a) con {$clients[$ci]->document_label}, confiero poder especial, amplio y suficiente a {$lawyer->name} ({$lawyer->professional_id}) para que me represente en el proceso: {$title}.", 'El apoderado queda facultado para recibir, conciliar, transigir, desistir, sustituir y reasumir.']],
                    ['Escrito de demanda', 'demanda', ["Señor Juez: {$judge}", "Referencia: {$title}. Radicado: {$case->case_number}.", 'HECHOS: Primero. Las partes celebraron un acuerdo cuyo incumplimiento motiva la presente acción. Segundo. Pese a los requerimientos, la parte demandada no ha cumplido con sus obligaciones.', 'PRETENSIONES: Que se declare la responsabilidad de la parte demandada y se le condene al pago de los perjuicios causados.']],
                ];
                foreach (array_slice($docs, 0, $i % 2 === 0 ? 2 : 1) as [$docTitle, $category, $paragraphs]) {
                    $path = "documents/{$case->id}/".Str::uuid().'.pdf';
                    $content = SimplePdf::make($docTitle.' — '.$case->case_number, $paragraphs);
                    Storage::disk('local')->put($path, $content);
                    $case->documents()->create([
                        'user_id' => $lawyer->id, 'title' => $docTitle, 'category' => $category,
                        'original_name' => Str::slug($docTitle).'.pdf', 'path' => $path,
                        'mime_type' => 'application/pdf', 'size' => strlen($content),
                    ]);
                }
            }
        }

        // ------------------------------------------------------------------
        // Citas
        // ------------------------------------------------------------------
        $appointmentRows = [
            [0, 0, $laura, 'Revisión de acuerdo de divorcio', 0, 9, AppointmentStatus::Confirmed, AppointmentMode::InPerson, 0],
            [2, 2, $jorge, 'Preparación de audiencia', 0, 15, AppointmentStatus::Confirmed, AppointmentMode::Video, 0],
            [null, null, $laura, 'Consulta inicial — sucesión', 1, 10, AppointmentStatus::Pending, AppointmentMode::InPerson, 150000, 'Martha Cecilia Rincón', '+57 315 222 3344'],
            [7, 7, $admin, 'Reunión con gerencia — contrato de suministro', 2, 11, AppointmentStatus::Confirmed, AppointmentMode::InPerson, 0],
            [12, null, $daniela, 'Asesoría — derecho de petición', 3, 16, AppointmentStatus::Pending, AppointmentMode::Phone, 80000],
            [null, null, $jorge, 'Consulta laboral — liquidación', 4, 8, AppointmentStatus::Pending, AppointmentMode::Video, 120000, 'Wilson Arley Pérez', '+57 316 444 5566'],
            [5, 5, $santiago, 'Entrega de documentos para demanda', 6, 14, AppointmentStatus::Confirmed, AppointmentMode::InPerson, 0],
            [10, 10, $admin, 'Seguimiento proceso de pertenencia', 8, 10, AppointmentStatus::Pending, AppointmentMode::InPerson, 0],
            [3, 3, $laura, 'Firma de poder', -2, 9, AppointmentStatus::Completed, AppointmentMode::InPerson, 0],
            [null, null, $laura, 'Consulta — régimen de visitas', -5, 11, AppointmentStatus::Completed, AppointmentMode::InPerson, 150000, 'Diana Marcela Ortiz', '+57 317 666 7788'],
            [8, 8, $jorge, 'Revisión dictamen de pérdida de capacidad', -7, 15, AppointmentStatus::Completed, AppointmentMode::Video, 0],
            [11, null, $jorge, 'Consulta — protección de datos', -12, 10, AppointmentStatus::NoShow, AppointmentMode::Phone, 100000],
            [13, 13, $admin, 'Comité jurídico mensual', -20, 8, AppointmentStatus::Completed, AppointmentMode::InPerson, 0],
            [6, null, $daniela, 'Asesoría contrato de arrendamiento', -3, 17, AppointmentStatus::Cancelled, AppointmentMode::InPerson, 90000],
        ];
        foreach ($appointmentRows as $r) {
            [$ci, $caseIdx, $lawyer, $title, $days, $hour, $st, $mode, $fee] = $r;
            $appointment = Appointment::create([
                'client_id' => $ci !== null ? $clients[$ci]->id : null,
                'legal_case_id' => $caseIdx !== null ? $cases[$caseIdx]->id : null,
                'user_id' => $lawyer->id,
                'contact_name' => $r[9] ?? null,
                'contact_phone' => $r[10] ?? null,
                'title' => $title,
                'starts_at' => now()->addDays($days)->setTime($hour, 0),
                'duration_minutes' => [30, 45, 60][abs($days) % 3],
                'mode' => $mode,
                'location' => $mode === AppointmentMode::InPerson ? 'Oficina principal — Sala de juntas' : ($mode === AppointmentMode::Video ? 'Google Meet' : null),
                'status' => $st,
                'fee' => $fee,
                'notes' => $fee > 0 ? 'Consulta con costo. Registrar el pago al finalizar.' : null,
            ]);

            if ($st === AppointmentStatus::Completed && $fee > 0 && $ci !== null) {
                $this->payment($clients[$ci], null, $lawyer, $fee, $appointment->starts_at->copy()->startOfDay(), 'Consulta: '.$title, $appointment);
            }
        }

        // ------------------------------------------------------------------
        // Comunicaciones con clientes
        // ------------------------------------------------------------------
        foreach (array_slice($cases, 0, 10) as $i => $case) {
            $case->client->communications()->create([
                'legal_case_id' => $case->id,
                'user_id' => $case->lawyer_id,
                'type' => [CommunicationType::Call, CommunicationType::Email, CommunicationType::WhatsApp][$i % 3],
                'subject' => ['Actualización del proceso', 'Envío de documentos', 'Confirmación de audiencia'][$i % 3],
                'body' => 'Se informó al cliente sobre el estado actual del proceso y los próximos pasos.',
                'created_at' => now()->subDays(mt_rand(1, 30)),
            ]);
        }

        // ------------------------------------------------------------------
        // Chat interno
        // ------------------------------------------------------------------
        $chat = [
            [$admin, $laura, 'Laura, ¿cómo vamos con el caso de divorcio de la señora López?', 180],
            [$laura, $admin, 'Bien, la audiencia ya está programada. Daniela está preparando las pruebas documentales.', 175],
            [$admin, $laura, 'Perfecto. Avísame si necesitas apoyo con el avalúo de los bienes.', 170],
            [$daniela, $laura, 'Doctora, ya cargué el poder firmado en el expediente.', 90],
            [$laura, $daniela, '¡Gracias Daniela! Revisa también los términos para el memorial de pruebas.', 85],
            [$jorge, $santiago, 'Santiago, mañana necesito el interrogatorio listo para la audiencia laboral.', 60],
            [$santiago, $jorge, 'Claro doctor, se lo envío hoy en la tarde.', 55],
            [$jorge, $admin, 'Carlos, registré los gastos de transporte de la diligencia en Medellín.', 30],
            [$daniela, $admin, 'Buenos días doctor, ¿me aprueba el gasto de copias del expediente?', 12],
        ];
        foreach ($chat as [$from, $to, $body, $minutesAgo]) {
            Message::create([
                'sender_id' => $from->id, 'receiver_id' => $to->id, 'body' => $body,
                'read_at' => $minutesAgo > 50 ? now()->subMinutes($minutesAgo - 5) : null,
                'created_at' => now()->subMinutes($minutesAgo), 'updated_at' => now()->subMinutes($minutesAgo),
            ]);
        }

        // ------------------------------------------------------------------
        // Notificaciones
        // ------------------------------------------------------------------
        $pendingExpense = Expense::query()->where('status', ExpenseStatus::Pending->value)->first();
        if ($pendingExpense) {
            $admin->notify(new AppNotification('Gasto pendiente de aprobación',
                "{$pendingExpense->user->name} registró un gasto de ".money($pendingExpense->amount),
                route('expenses.show', $pendingExpense, false), 'receipt', 'amber'));
        }
        $nextHearing = Hearing::query()->upcoming()->first();
        if ($nextHearing) {
            foreach ([$admin, $nextHearing->user] as $u) {
                $u?->notify(new AppNotification('Audiencia próxima',
                    "{$nextHearing->title} — {$nextHearing->scheduled_at->format('d/m/Y h:i a')}",
                    route('hearings.edit', $nextHearing, false), 'scale', 'rose'));
            }
        }
        $laura->notify(new AppNotification('Caso asignado', "Se le asignó el caso {$cases[3]->case_number}: {$cases[3]->title}",
            route('cases.show', $cases[3], false), 'briefcase', 'indigo'));
        $daniela->notify(new AppNotification('Caso asignado', "Se le asignó como asistente del caso {$cases[0]->case_number}",
            route('cases.show', $cases[0], false), 'briefcase', 'indigo'));

        // ------------------------------------------------------------------
        // Conversación de ejemplo con el asistente de IA
        // ------------------------------------------------------------------
        $conversation = AiConversation::create(['user_id' => $admin->id, 'title' => '¿Qué audiencias tengo esta semana?']);
        $question = '¿Qué audiencias tengo esta semana?';
        $conversation->messages()->create(['role' => 'user', 'content' => $question]);
        $conversation->messages()->create(['role' => 'assistant', 'content' => (new LocalAssistant)->reply($admin, $question), 'provider' => 'local']);

        $this->renumberReceipts();
    }

    /** Numera los recibos en orden cronológico. */
    private function renumberReceipts(): void
    {
        Payment::query()->update(['receipt_number' => DB::raw("'TMP-' || id")]);

        $counters = [];
        Payment::query()->orderBy('paid_at')->orderBy('id')->get()->each(function (Payment $p) use (&$counters) {
            $year = $p->paid_at->format('Y');
            $counters[$year] = ($counters[$year] ?? 0) + 1;
            $p->update(['receipt_number' => sprintf('REC-%s-%05d', $year, $counters[$year])]);
        });
    }

    private function payment(Client $client, ?LegalCase $case, User $user, float $amount, Carbon $date, string $concept, ?Appointment $appointment = null): void
    {
        $year = $date->format('Y');
        $this->receiptCounters[$year] = ($this->receiptCounters[$year] ?? 0) + 1;

        Payment::create([
            'receipt_number' => sprintf('REC-%s-%05d', $year, $this->receiptCounters[$year]),
            'client_id' => $client->id,
            'legal_case_id' => $case?->id,
            'appointment_id' => $appointment?->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'method' => [PaymentMethod::Transfer, PaymentMethod::Cash, PaymentMethod::Card, PaymentMethod::Deposit][mt_rand(0, 3)],
            'paid_at' => $date,
            'concept' => $concept,
            'reference' => mt_rand(0, 1) ? 'TRX-'.mt_rand(100000, 999999) : null,
        ]);
    }
}
