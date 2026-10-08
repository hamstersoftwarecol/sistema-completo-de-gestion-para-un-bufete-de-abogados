<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modo demostración
    |--------------------------------------------------------------------------
    | Muestra las cuentas de prueba en la pantalla de inicio de sesión.
    */
    'demo_mode' => env('DEMO_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | setup.php (instalación / restablecer datos de demostración)
    |--------------------------------------------------------------------------
    */
    'setup' => [
        'web_enabled' => env('SETUP_WEB_ENABLED', true),
        'allow_remote' => env('SETUP_ALLOW_REMOTE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Archivos
    |--------------------------------------------------------------------------
    */
    'upload_max_kb' => (int) env('UPLOAD_MAX_KB', 51200),

    'document_mimes' => 'pdf,jpg,jpeg,png,gif,webp,mp4,webm,mov,m4v,mp3,m4a,ogg,wav,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt',

    'receipt_mimes' => 'pdf,jpg,jpeg,png,webp',

    /*
    |--------------------------------------------------------------------------
    | Catálogos
    |--------------------------------------------------------------------------
    */
    'document_types' => [
        'CC' => 'Cédula de ciudadanía',
        'CE' => 'Cédula de extranjería',
        'NIT' => 'NIT',
        'PAS' => 'Pasaporte',
        'TI' => 'Tarjeta de identidad',
        'DNI' => 'DNI',
        'RFC' => 'RFC',
        'OTRO' => 'Otro',
    ],

    'document_categories' => [
        'demanda' => 'Demanda',
        'contestacion' => 'Contestación',
        'poder' => 'Poder',
        'prueba' => 'Prueba',
        'auto' => 'Auto / Providencia',
        'sentencia' => 'Sentencia',
        'contrato' => 'Contrato',
        'memorial' => 'Memorial',
        'correspondencia' => 'Correspondencia',
        'multimedia' => 'Audio / Video',
        'otro' => 'Otro',
    ],

    'party_roles' => [
        'demandante' => 'Demandante',
        'demandado' => 'Demandado',
        'denunciante' => 'Denunciante',
        'denunciado' => 'Denunciado / Imputado',
        'victima' => 'Víctima',
        'tercero' => 'Tercero interviniente',
        'testigo' => 'Testigo',
        'perito' => 'Perito',
        'apoderado_contraparte' => 'Apoderado de la contraparte',
        'fiscal' => 'Fiscal',
        'otro' => 'Otro',
    ],

    'hearing_types' => [
        'Audiencia inicial',
        'Audiencia de conciliación',
        'Audiencia de pruebas',
        'Audiencia de instrucción y juzgamiento',
        'Audiencia de alegatos',
        'Lectura de fallo',
        'Audiencia preliminar',
        'Audiencia de imputación',
        'Diligencia de inspección',
        'Otra',
    ],

    'expense_categories' => [
        'tasas' => 'Tasas y aranceles judiciales',
        'notaria' => 'Notaría y registro',
        'copias' => 'Copias y autenticaciones',
        'transporte' => 'Transporte y viáticos',
        'mensajeria' => 'Mensajería / notificaciones',
        'peritos' => 'Honorarios de peritos',
        'publicaciones' => 'Edictos y publicaciones',
        'otro' => 'Otro',
    ],

    /*
    |--------------------------------------------------------------------------
    | Temas de color (Apariencia)
    |--------------------------------------------------------------------------
    */
    'themes' => [
        'indigo' => ['label' => 'Índigo', 'shades' => [50 => '#eef2ff', 100 => '#e0e7ff', 200 => '#c7d2fe', 300 => '#a5b4fc', 400 => '#818cf8', 500 => '#6366f1', 600 => '#4f46e5', 700 => '#4338ca', 800 => '#3730a3', 900 => '#312e81', 950 => '#1e1b4b']],
        'blue' => ['label' => 'Azul', 'shades' => [50 => '#eff6ff', 100 => '#dbeafe', 200 => '#bfdbfe', 300 => '#93c5fd', 400 => '#60a5fa', 500 => '#3b82f6', 600 => '#2563eb', 700 => '#1d4ed8', 800 => '#1e40af', 900 => '#1e3a8a', 950 => '#172554']],
        'emerald' => ['label' => 'Esmeralda', 'shades' => [50 => '#ecfdf5', 100 => '#d1fae5', 200 => '#a7f3d0', 300 => '#6ee7b7', 400 => '#34d399', 500 => '#10b981', 600 => '#059669', 700 => '#047857', 800 => '#065f46', 900 => '#064e3b', 950 => '#022c22']],
        'teal' => ['label' => 'Turquesa', 'shades' => [50 => '#f0fdfa', 100 => '#ccfbf1', 200 => '#99f6e4', 300 => '#5eead4', 400 => '#2dd4bf', 500 => '#14b8a6', 600 => '#0d9488', 700 => '#0f766e', 800 => '#115e59', 900 => '#134e4a', 950 => '#042f2e']],
        'violet' => ['label' => 'Violeta', 'shades' => [50 => '#f5f3ff', 100 => '#ede9fe', 200 => '#ddd6fe', 300 => '#c4b5fd', 400 => '#a78bfa', 500 => '#8b5cf6', 600 => '#7c3aed', 700 => '#6d28d9', 800 => '#5b21b6', 900 => '#4c1d95', 950 => '#2e1065']],
        'rose' => ['label' => 'Rosa', 'shades' => [50 => '#fff1f2', 100 => '#ffe4e6', 200 => '#fecdd3', 300 => '#fda4af', 400 => '#fb7185', 500 => '#f43f5e', 600 => '#e11d48', 700 => '#be123c', 800 => '#9f1239', 900 => '#881337', 950 => '#4c0519']],
        'red' => ['label' => 'Granate', 'shades' => [50 => '#fef2f2', 100 => '#fee2e2', 200 => '#fecaca', 300 => '#fca5a5', 400 => '#f87171', 500 => '#ef4444', 600 => '#dc2626', 700 => '#b91c1c', 800 => '#991b1b', 900 => '#7f1d1d', 950 => '#450a0a']],
        'amber' => ['label' => 'Ámbar', 'shades' => [50 => '#fffbeb', 100 => '#fef3c7', 200 => '#fde68a', 300 => '#fcd34d', 400 => '#fbbf24', 500 => '#f59e0b', 600 => '#d97706', 700 => '#b45309', 800 => '#92400e', 900 => '#78350f', 950 => '#451a03']],
        'slate' => ['label' => 'Pizarra', 'shades' => [50 => '#f8fafc', 100 => '#f1f5f9', 200 => '#e2e8f0', 300 => '#cbd5e1', 400 => '#94a3b8', 500 => '#64748b', 600 => '#475569', 700 => '#334155', 800 => '#1e293b', 900 => '#0f172a', 950 => '#020617']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asistente de IA
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'timeout' => (int) env('AI_TIMEOUT', 60),
        'history_limit' => 20,
        'default_gemini_model' => 'gemini-2.5-flash',
        'default_openai_model' => 'gpt-4o-mini',
        'default_openai_base_url' => 'https://api.openai.com/v1',
        'default_system_prompt' => 'Eres LexIA, el asistente jurídico interno de un bufete de abogados. Respondes siempre en español, con lenguaje claro y profesional. Ayudas a redactar escritos, resumir expedientes, explicar procedimientos y organizar la agenda del abogado. Cuando la pregunta se refiera a casos, audiencias, citas, pagos o gastos del bufete, utiliza únicamente los datos del contexto proporcionado y no inventes información. Recuerda que tus respuestas no sustituyen el criterio profesional del abogado y que debe verificarse la normativa vigente de la jurisdicción aplicable.',
    ],
];
