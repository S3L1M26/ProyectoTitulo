<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Aprendiz;
use App\Models\AreaInteres;
use Illuminate\Support\Facades\Hash;

class AprendizTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un usuario estudiante de prueba
        $student = User::updateOrCreate(
            ['email' => 'estudiante.test@example.com'],
            [
                'name' => 'Estudiante Test',
                'password' => Hash::make('password'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil de aprendiz
        $aprendiz = Aprendiz::updateOrCreate(
            ['user_id' => $student->id],
            [
                'semestre' => 5,
                'objetivos' => 'Me encuentro en 5to semestre y no tengo claro hacia qué área del desarrollo web enfocar mi carrera. Me gustaría conocer las diferencias reales entre frontend y backend, saber cómo es el día a día de un desarrollador, qué oportunidades laborales existen y cómo decidir mi especialización.'
            ]
        );

        // Asociar algunas áreas de interés (usando nombres correctos)
        $areasIds = AreaInteres::whereIn('nombre', [
            'Desarrollo Web Frontend', 
            'Desarrollo Web Backend', 
            'Análisis de Datos'
        ])->pluck('id')->toArray();
            
        if (!empty($areasIds)) {
            $aprendiz->areasInteres()->sync($areasIds);
        }

        // Crear un segundo usuario con perfil incompleto para testing
        $incompleteStudent = User::updateOrCreate(
            ['email' => 'estudiante.incompleto@example.com'],
            [
                'name' => 'Estudiante Incompleto',
                'password' => Hash::make('password'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil parcialmente completo (solo semestre)
        Aprendiz::updateOrCreate(
            ['user_id' => $incompleteStudent->id],
            [
                'semestre' => 3,
                'objetivos' => '', // Sin objetivos
            ]
        );
        // Sin áreas de interés

        // Crear usuarios mentores de prueba
        $mentor = User::updateOrCreate(
            ['email' => 'mentor.completo@example.com'],
            [
                'name' => 'Mentor Completo',
                'password' => Hash::make('password'),
                'role' => 'mentor',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil completo de mentor
        $mentorProfile = \App\Models\Mentor::updateOrCreate(
            ['user_id' => $mentor->id],
            [
                'experiencia' => 'Tengo 8 años de experiencia como desarrollador full-stack en empresas tecnológicas. He trabajado tanto en startups como en corporaciones grandes, lo que me ha dado una perspectiva amplia del mercado laboral en tecnología.',
                'biografia' => 'Como egresado de ingeniería en sistemas, entiendo la incertidumbre que sienten los estudiantes universitarios sobre su futuro profesional. Mi objetivo es compartir mi experiencia real del mundo laboral TI para ayudar a estudiantes a descubrir qué área les conviene más según sus intereses y personalidad.',
                'años_experiencia' => 8,
                'disponibilidad' => 'Lunes a Viernes: 18:00-21:00, Sábados: 09:00-12:00',
                'disponibilidad_detalle' => 'Conversaciones de orientación de 1 hora por videollamada. Puedo compartir mi experiencia, resolver dudas sobre el mercado laboral y ayudar a identificar áreas de interés.',
                'calificacionPromedio' => 4.8,
            ]
        );

        // Asociar áreas de interés al mentor completo
        $mentorAreasIds = AreaInteres::whereIn('nombre', [
            'Desarrollo Web Frontend',
            'Desarrollo Web Backend',
            'DevOps'
        ])->pluck('id')->toArray();
        
        if (!empty($mentorAreasIds)) {
            $mentorProfile->areasInteres()->sync($mentorAreasIds);
        }

        $incompleteMentor = User::updateOrCreate(
            ['email' => 'mentor.incompleto@example.com'],
            [
                'name' => 'Mentor Incompleto',
                'password' => Hash::make('password'),
                'role' => 'mentor',
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil parcialmente completo de mentor (solo algunos campos)
        \App\Models\Mentor::updateOrCreate(
            ['user_id' => $incompleteMentor->id],
            [
                'experiencia' => 'Desarrollador con experiencia en JavaScript.',
                'años_experiencia' => 3,
                // Sin biografia, disponibilidad, disponibilidad_detalle, ni áreas de interés
            ]
        );

        // Crear más estudiantes para testing diverso
        $this->createAdditionalStudents();
        
        // Crear más mentores para testing robusto  
        $this->createAdditionalMentors();

        echo "Usuario estudiante completo creado: {$student->email}\n";
        echo "Usuario estudiante incompleto creado: {$incompleteStudent->email}\n";
        echo "Usuario mentor completo creado: {$mentor->email}\n";
        echo "Usuario mentor incompleto creado: {$incompleteMentor->email}\n";
        echo "Seeders de test ampliados: +4 estudiantes y +8 mentores\n";
        echo "Contraseña para todos: password\n";
    }

    /**
     * Crear estudiantes adicionales para testing
     */
    private function createAdditionalStudents()
    {
        $students = [
            [
                'email' => 'ana.frontend@example.com',
                'name' => 'Ana Rodríguez',
                'semestre' => 4,
                'objetivos' => 'Estoy en 4to semestre y me interesa el diseño y la parte visual de las aplicaciones, pero no estoy segura de las oportunidades laborales reales que existen en frontend y UX. Me gustaría conocer cómo es trabajar en estas áreas y qué se necesita para destacar profesionalmente.',
                'areas' => ['Desarrollo Web Frontend', 'Diseño UX/UI']
            ],
            [
                'email' => 'carlos.backend@example.com', 
                'name' => 'Carlos Mendoza',
                'semestre' => 6,
                'objetivos' => 'Me atrae la lógica de programación y los sistemas complejos, pero no tengo claro qué significa realmente trabajar en backend o DevOps. Quiero entender las diferencias entre estas áreas y conocer el día a día de estos profesionales para tomar una mejor decisión.',
                'areas' => ['Desarrollo Web Backend', 'DevOps']
            ],
            [
                'email' => 'maria.data@example.com',
                'name' => 'María González',
                'semestre' => 7,
                'objetivos' => 'Los números y la estadística me fascinan, pero no sé si el análisis de datos o la inteligencia artificial son campos adecuados para mí. Busco orientación sobre las oportunidades reales en estos sectores y cómo es la rutina de trabajo.',
                'areas' => ['Análisis de Datos', 'Inteligencia Artificial']
            ],
            [
                'email' => 'luis.mobile@example.com',
                'name' => 'Luis Torres',
                'semestre' => 3,
                'objetivos' => 'Uso mucho mi celular y me llama la atención el desarrollo móvil, pero no tengo idea de cómo es realmente trabajar creando apps. Me gustaría conocer la industria móvil, sus desafíos y oportunidades laborales.',
                'areas' => ['Desarrollo Mobile', 'Desarrollo Web Frontend']
            ]
        ];

        foreach ($students as $studentData) {
            $user = User::updateOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'email_verified_at' => now(),
                ]
            );

            $aprendiz = Aprendiz::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'semestre' => $studentData['semestre'],
                    'objetivos' => $studentData['objetivos']
                ]
            );

            // Asociar áreas de interés
            $areasIds = AreaInteres::whereIn('nombre', $studentData['areas'])->pluck('id')->toArray();
            if (!empty($areasIds)) {
                $aprendiz->areasInteres()->sync($areasIds);
            }
        }
    }

    /**
     * Crear mentores adicionales para testing robusto
     */
    private function createAdditionalMentors()
    {
        $mentors = [
            [
                'email' => 'pedro.senior@example.com',
                'name' => 'Pedro Ramírez',
                'experiencia' => 'Senior Developer con 12 años en la industria. He trabajado en startups desde sus inicios hasta empresas Fortune 500, viviendo diferentes culturas corporativas y desafíos técnicos.',
                'biografia' => 'He pasado por todas las etapas de crecimiento profesional en tech. Puedo ayudarte a entender cómo es realmente el trabajo en diferentes tipos de empresas, los retos del liderazgo técnico y las decisiones de carrera que realmente importan.',
                'anos_experiencia' => 12,
                'disponibilidad' => 'Martes y Jueves: 19:00-21:00',
                'detalle' => 'Sesiones de 90 minutos para conversar sobre experiencias reales, cultura empresarial y crecimiento profesional.',
                'rating' => 4.9,
                'disponible' => true,
                'areas' => ['Desarrollo Web Backend', 'DevOps', 'Arquitectura de Software']
            ],
            [
                'email' => 'laura.ui@example.com',
                'name' => 'Laura Jiménez',
                'experiencia' => 'UX/UI Designer con 6 años trabajando en productos digitales. He visto cómo el diseño impacta realmente en los negocios y la experiencia del usuario.',
                'biografia' => 'Puedo contarte sobre las realidades del diseño UX/UI: desde las frustraciones con stakeholders hasta la satisfacción de ver usuarios felices. Te ayudo a entender si esta área se alinea con tu personalidad y objetivos profesionales.',
                'anos_experiencia' => 6,
                'disponibilidad' => 'Lunes, Miércoles, Viernes: 17:00-19:00',
                'detalle' => 'Conversaciones sobre el día a día del diseño, diferencias entre UX y UI, y oportunidades reales en el mercado.',
                'rating' => 4.7,
                'disponible' => true,
                'areas' => ['Diseño UX/UI', 'Desarrollo Web Frontend']
            ],
            [
                'email' => 'jorge.data@example.com',
                'name' => 'Jorge Salinas',
                'experiencia' => 'Data Scientist con 8 años trabajando con datos en diferentes industrias. He visto la evolución del campo y las oportunidades reales que existen.',
                'biografia' => 'Te puedo contar sobre las realidades del trabajo con datos: desde limpiar datasets hasta presentar resultados a ejecutivos. Te ayudo a entender si te gusta realmente trabajar con números y qué significa ser científico de datos en la práctica.',
                'anos_experiencia' => 8,
                'disponibilidad' => 'Fines de semana: 10:00-14:00',
                'detalle' => 'Conversaciones sobre diferentes roles en datos, industrias donde trabajar, y cómo es el día típico de un data scientist.',
                'rating' => 4.6,
                'disponible' => true,
                'areas' => ['Análisis de Datos', 'Inteligencia Artificial']
            ],
            [
                'email' => 'sofia.mobile@example.com',
                'name' => 'Sofía López',
                'experiencia' => 'Desarrolladora móvil con 7 años creando apps para diferentes mercados. He trabajado tanto en productos propios como para clientes, viendo diferentes modelos de negocio.',
                'biografia' => 'Puedo compartir cómo es realmente el desarrollo móvil: los retos técnicos, las diferencias entre plataformas, y las oportunidades laborales. Te ayudo a entender si te emociona realmente crear productos que la gente usa en su día a día.',
                'anos_experiencia' => 7,
                'disponibilidad' => 'Martes a Jueves: 20:00-22:00',
                'detalle' => 'Conversaciones sobre la industria móvil, tipos de proyectos, y cómo elegir tu especialización en este campo.',
                'rating' => 4.8,
                'disponible' => true,
                'areas' => ['Desarrollo Mobile', 'Desarrollo Web Frontend']
            ],
            [
                'email' => 'ricardo.devops@example.com',
                'name' => 'Ricardo Moreno',
                'experiencia' => 'DevOps Engineer con 10 años trabajando en infraestructura y automatización. He visto cómo ha evolucionado esta área y las oportunidades que ofrece.',
                'biografia' => 'Puedo contarte sobre el mundo DevOps desde adentro: cómo es trabajar con sistemas críticos, la responsabilidad que conlleva, y las diferentes especializaciones posibles. Te ayudo a entender si te motiva el lado más técnico e infraestructural de la tecnología.',
                'anos_experiencia' => 10,
                'disponibilidad' => 'Lunes a Miércoles: 18:00-20:00',
                'detalle' => 'Conversaciones sobre infraestructura, automatización, responsabilidades reales y oportunidades de crecimiento en DevOps.',
                'rating' => 4.5,
                'disponible' => false, // No disponible para testing
                'areas' => ['DevOps', 'Desarrollo Web Backend']
            ],
            [
                'email' => 'camila.junior@example.com',
                'name' => 'Camila Ruiz',
                'experiencia' => 'Desarrolladora Full-Stack con 3 años de experiencia. Todavía recuerdo vívidamente mi transición de estudiante a profesional y todos los miedos e incertidumbres que tuve.',
                'biografia' => 'Puedo compartir cómo fue mi experiencia reciente al entrar al mundo laboral tech: desde las primeras entrevistas hasta encontrar mi lugar en un equipo. Mi perspectiva es muy cercana a la tuya porque acabo de pasar por lo mismo.',
                'anos_experiencia' => 3,
                'disponibilidad' => 'Todos los días: 16:00-18:00',
                'detalle' => 'Conversaciones relajadas sobre la transición universidad-trabajo, primeros empleos y cómo navegar el inicio de carrera.',
                'rating' => 4.2,
                'disponible' => true,
                'areas' => ['Desarrollo Web Frontend', 'Desarrollo Web Backend']
            ],
            [
                'email' => 'diego.freelance@example.com',
                'name' => 'Diego Fernández',
                'experiencia' => 'Freelancer con 9 años trabajando de forma independiente con clientes de diferentes países. He vivido las ventajas y desafíos del trabajo remoto e independiente.',
                'biografia' => 'Puedo contarte sobre las realidades del freelancing: desde conseguir clientes hasta manejar la incertidumbre financiera. Te ayudo a entender si tu personalidad se adapta al trabajo independiente y qué implica realmente esta modalidad.',
                'anos_experiencia' => 9,
                'disponibilidad' => 'Horarios muy flexibles',
                'detalle' => 'Conversaciones sobre trabajo independiente, gestión de clientes, y si el freelancing es para ti.',
                'rating' => 4.4,
                'disponible' => false, // Ocupado con proyectos
                'areas' => ['Desarrollo Web Frontend', 'Desarrollo Web Backend', 'Gestión de Proyectos']
            ],
            [
                'email' => 'valeria.startup@example.com',
                'name' => 'Valeria Herrera',
                'experiencia' => 'CTO y co-fundadora de 2 startups. 11 años viviendo el mundo emprendedor tech, con sus éxitos y fracasos. He visto cómo se construyen productos desde cero.',
                'biografia' => 'Puedo compartir las realidades del emprendimiento tecnológico: desde las noches sin dormir hasta la satisfacción de ver tu producto impactar usuarios reales. Te ayudo a entender si tienes el perfil y la mentalidad para el mundo startup.',
                'anos_experiencia' => 11,
                'disponibilidad' => 'Sábados: 09:00-12:00',
                'detalle' => 'Conversaciones sobre emprendimiento, liderazgo técnico, y el ecosistema startup desde adentro.',
                'rating' => 4.9,
                'disponible' => true,
                'areas' => ['Desarrollo Web Backend', 'Arquitectura de Software', 'Gestión de Proyectos']
            ]
        ];

        foreach ($mentors as $mentorData) {
            $user = User::updateOrCreate(
                ['email' => $mentorData['email']],
                [
                    'name' => $mentorData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'mentor',
                    'email_verified_at' => now(),
                ]
            );

            $mentorProfile = \App\Models\Mentor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'experiencia' => $mentorData['experiencia'],
                    'biografia' => $mentorData['biografia'],
                    'años_experiencia' => $mentorData['anos_experiencia'],
                    'disponibilidad' => $mentorData['disponibilidad'],
                    'disponibilidad_detalle' => $mentorData['detalle'],
                    'calificacionPromedio' => $mentorData['rating'],
                    'disponible_ahora' => $mentorData['disponible'],
                ]
            );

            // Asociar áreas de interés
            $areasIds = AreaInteres::whereIn('nombre', $mentorData['areas'])->pluck('id')->toArray();
            if (!empty($areasIds)) {
                $mentorProfile->areasInteres()->sync($areasIds);
            }
        }
    }
}
