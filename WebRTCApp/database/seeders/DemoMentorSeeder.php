<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mentor;
use App\Models\AreaInteres;
use App\Models\MentorReview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoMentorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates demo mentor accounts based on AprendizTestSeeder mentors,
     * with @demo.com emails, verified emails, no requests/mentorias,
     * but with at least 3 reviews each to calculate rating.
     */
    public function run(): void
    {
        // Datos de mentores desde AprendizTestSeeder adaptados para demo
        $mentors = [
            [
                'email' => 'pedro.ramirez@demo.com',
                'name' => 'Pedro Ramírez',
                'experiencia' => 'Senior Developer con 12 años en la industria. He trabajado en startups desde sus inicios hasta empresas Fortune 500, viviendo diferentes culturas corporativas y desafíos técnicos.',
                'biografia' => 'He pasado por todas las etapas de crecimiento profesional en tech. Puedo ayudarte a entender cómo es realmente el trabajo en diferentes tipos de empresas, los retos del liderazgo técnico y las decisiones de carrera que realmente importan.',
                'anos_experiencia' => 12,
                'disponibilidad' => 'Martes y Jueves: 19:00-21:00',
                'detalle' => 'Sesiones de 90 minutos para conversar sobre experiencias reales, cultura empresarial y crecimiento profesional.',
                'disponible' => true,
                'areas' => ['Desarrollo Web Backend', 'DevOps', 'Arquitectura de Software'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Excelente mentor, compartió experiencias muy valiosas sobre liderazgo técnico.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 5, 'comment' => 'Me ayudó a entender las diferencias entre trabajar en startup vs empresa grande.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Muy buena orientación sobre cultura empresarial y DevOps.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                    ['rating' => 5, 'comment' => 'Sus consejos sobre decisiones de carrera fueron muy útiles.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                ]
            ],
            [
                'email' => 'laura.jimenez@demo.com',
                'name' => 'Laura Jiménez',
                'experiencia' => 'UX/UI Designer con 6 años trabajando en productos digitales. He visto cómo el diseño impacta realmente en los negocios y la experiencia del usuario.',
                'biografia' => 'Puedo contarte sobre las realidades del diseño UX/UI: desde las frustraciones con stakeholders hasta la satisfacción de ver usuarios felices. Te ayudo a entender si esta área se alinea con tu personalidad y objetivos profesionales.',
                'anos_experiencia' => 6,
                'disponibilidad' => 'Lunes, Miércoles, Viernes: 17:00-19:00',
                'detalle' => 'Conversaciones sobre el día a día del diseño, diferencias entre UX y UI, y oportunidades reales en el mercado.',
                'disponible' => true,
                'areas' => ['Diseño UX/UI', 'Desarrollo Web Frontend'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Clarísima explicando las diferencias entre UX y UI, ahora sé qué me gusta más.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Me mostró herramientas y recursos reales que usan en la industria.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                    ['rating' => 5, 'comment' => 'Súper honesta sobre los desafíos del diseño, me ayudó a tomar una decisión.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                ]
            ],
            [
                'email' => 'jorge.salinas@demo.com',
                'name' => 'Jorge Salinas',
                'experiencia' => 'Data Scientist con 8 años trabajando con datos en diferentes industrias. He visto la evolución del campo y las oportunidades reales que existen.',
                'biografia' => 'Te puedo contar sobre las realidades del trabajo con datos: desde limpiar datasets hasta presentar resultados a ejecutivos. Te ayudo a entender si te gusta realmente trabajar con números y qué significa ser científico de datos en la práctica.',
                'anos_experiencia' => 8,
                'disponibilidad' => 'Fines de semana: 10:00-14:00',
                'detalle' => 'Conversaciones sobre diferentes roles en datos, industrias donde trabajar, y cómo es el día típico de un data scientist.',
                'disponible' => true,
                'areas' => ['Análisis de Datos', 'Inteligencia Artificial'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Explicó de forma muy práctica cómo es trabajar con datos día a día.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Buen overview de las diferentes industrias donde trabajan data scientists.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                    ['rating' => 5, 'comment' => 'Me ayudó a decidir si realmente me gusta analizar datos o solo la idea.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Muy realista sobre las expectativas vs la realidad en data science.', 'addressed_interests' => 'partial', 'interests_clarity' => 4],
                ]
            ],
            [
                'email' => 'sofia.lopez@demo.com',
                'name' => 'Sofía López',
                'experiencia' => 'Desarrolladora móvil con 7 años creando apps para diferentes mercados. He trabajado tanto en productos propios como para clientes, viendo diferentes modelos de negocio.',
                'biografia' => 'Puedo compartir cómo es realmente el desarrollo móvil: los retos técnicos, las diferencias entre plataformas, y las oportunidades laborales. Te ayudo a entender si te emociona realmente crear productos que la gente usa en su día a día.',
                'anos_experiencia' => 7,
                'disponibilidad' => 'Martes a Jueves: 20:00-22:00',
                'detalle' => 'Conversaciones sobre la industria móvil, tipos de proyectos, y cómo elegir tu especialización en este campo.',
                'disponible' => true,
                'areas' => ['Desarrollo Mobile', 'Desarrollo Web Frontend'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Increíble cómo explicó las diferencias entre iOS y Android desde su experiencia.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 5, 'comment' => 'Me mostró proyectos reales y cómo es el día a día de un dev móvil.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Buena orientación sobre las oportunidades laborales en desarrollo mobile.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                ]
            ],
            [
                'email' => 'ricardo.moreno@demo.com',
                'name' => 'Ricardo Moreno',
                'experiencia' => 'DevOps Engineer con 10 años trabajando en infraestructura y automatización. He visto cómo ha evolucionado esta área y las oportunidades que ofrece.',
                'biografia' => 'Puedo contarte sobre el mundo DevOps desde adentro: cómo es trabajar con sistemas críticos, la responsabilidad que conlleva, y las diferentes especializaciones posibles. Te ayudo a entender si te motiva el lado más técnico e infraestructural de la tecnología.',
                'anos_experiencia' => 10,
                'disponibilidad' => 'Lunes a Miércoles: 18:00-20:00',
                'detalle' => 'Conversaciones sobre infraestructura, automatización, responsabilidades reales y oportunidades de crecimiento en DevOps.',
                'disponible' => false, // No disponible para demostrar filtrado
                'areas' => ['DevOps', 'Desarrollo Web Backend'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Clarísimo explicando qué es DevOps realmente y qué hace un ingeniero.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Me ayudó a entender si tengo el perfil para trabajar con infraestructura.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                    ['rating' => 5, 'comment' => 'Excelente mentoría, muy honesto sobre responsabilidades y estrés.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                ]
            ],
            [
                'email' => 'camila.ruiz@demo.com',
                'name' => 'Camila Ruiz',
                'experiencia' => 'Desarrolladora Full-Stack con 3 años de experiencia. Todavía recuerdo vívidamente mi transición de estudiante a profesional y todos los miedos e incertidumbres que tuve.',
                'biografia' => 'Puedo compartir cómo fue mi experiencia reciente al entrar al mundo laboral tech: desde las primeras entrevistas hasta encontrar mi lugar en un equipo. Mi perspectiva es muy cercana a la tuya porque acabo de pasar por lo mismo.',
                'anos_experiencia' => 3,
                'disponibilidad' => 'Todos los días: 16:00-18:00',
                'detalle' => 'Conversaciones relajadas sobre la transición universidad-trabajo, primeros empleos y cómo navegar el inicio de carrera.',
                'disponible' => true,
                'areas' => ['Desarrollo Web Frontend', 'Desarrollo Web Backend'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Súper cercana y honesta, me sentí muy identificado con su experiencia.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 5, 'comment' => 'Me ayudó a sentirme más confiado para enfrentar entrevistas.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Buena onda y muy práctica, compartió tips reales de su experiencia reciente.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                    ['rating' => 5, 'comment' => 'Perfecta para estudiantes que están cerca de egresar, entiende la ansiedad.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                ]
            ],
            [
                'email' => 'diego.fernandez@demo.com',
                'name' => 'Diego Fernández',
                'experiencia' => 'Freelancer con 9 años trabajando de forma independiente con clientes de diferentes países. He vivido las ventajas y desafíos del trabajo remoto e independiente.',
                'biografia' => 'Puedo contarte sobre las realidades del freelancing: desde conseguir clientes hasta manejar la incertidumbre financiera. Te ayudo a entender si tu personalidad se adapta al trabajo independiente y qué implica realmente esta modalidad.',
                'anos_experiencia' => 9,
                'disponibilidad' => 'Horarios muy flexibles',
                'detalle' => 'Conversaciones sobre trabajo independiente, gestión de clientes, y si el freelancing es para ti.',
                'disponible' => false, // Ocupado con proyectos
                'areas' => ['Desarrollo Web Frontend', 'Desarrollo Web Backend', 'Gestión de Proyectos'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Me abrió los ojos sobre las realidades del freelancing, muy honesto.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Buen insight sobre gestión financiera y conseguir clientes.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                    ['rating' => 5, 'comment' => 'Me ayudó a decidir si realmente quiero ser freelancer o empleado.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                ]
            ],
            [
                'email' => 'valeria.herrera@demo.com',
                'name' => 'Valeria Herrera',
                'experiencia' => 'CTO y co-fundadora de 2 startups. 11 años viviendo el mundo emprendedor tech, con sus éxitos y fracasos. He visto cómo se construyen productos desde cero.',
                'biografia' => 'Puedo compartir las realidades del emprendimiento tecnológico: desde las noches sin dormir hasta la satisfacción de ver tu producto impactar usuarios reales. Te ayudo a entender si tienes el perfil y la mentalidad para el mundo startup.',
                'anos_experiencia' => 11,
                'disponibilidad' => 'Sábados: 09:00-12:00',
                'detalle' => 'Conversaciones sobre emprendimiento, liderazgo técnico, y el ecosistema startup desde adentro.',
                'disponible' => true,
                'areas' => ['Desarrollo Web Backend', 'Arquitectura de Software', 'Gestión de Proyectos'],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Inspiradora y muy realista sobre emprendimiento tech.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 5, 'comment' => 'Me ayudó a entender si tengo el perfil para el mundo startup.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                    ['rating' => 4, 'comment' => 'Excelente visión de liderazgo técnico y construcción de equipos.', 'addressed_interests' => 'yes', 'interests_clarity' => 4],
                    ['rating' => 5, 'comment' => 'Súper honesta sobre fracasos y éxitos, muy valiosa la mentoría.', 'addressed_interests' => 'yes', 'interests_clarity' => 5],
                ]
            ],
        ];

        // Crear estudiantes demo para asociar reviews (reutilizamos de DemoStudentSeeder)
        $demoStudents = User::where('email', 'like', '%@demo.com')
            ->where('role', 'student')
            ->get();

        if ($demoStudents->isEmpty()) {
            $this->command->warn('⚠ No hay estudiantes demo. Ejecuta DemoStudentSeeder primero.');
            return;
        }

        foreach ($mentors as $mentorData) {
            // Crear usuario mentor con email verificado
            $user = User::updateOrCreate(
                ['email' => $mentorData['email']],
                [
                    'name' => $mentorData['name'],
                    'password' => Hash::make('password'), // Contraseña por defecto
                    'role' => 'mentor',
                    'email_verified_at' => now(), // Email verificado
                    'is_active' => true,
                ]
            );

            // Crear perfil de mentor completo
            $mentorProfile = Mentor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'experiencia' => $mentorData['experiencia'],
                    'biografia' => $mentorData['biografia'],
                    'años_experiencia' => $mentorData['anos_experiencia'],
                    'disponibilidad' => $mentorData['disponibilidad'],
                    'disponibilidad_detalle' => $mentorData['detalle'],
                    'disponible_ahora' => $mentorData['disponible'],
                    'calificacionPromedio' => 0.0, // Se calculará después de las reseñas
                    'cv_verified' => false, // Sin verificar - se cargará manualmente
                ]
            );

            // Asociar áreas de interés
            $areasIds = AreaInteres::whereIn('nombre', $mentorData['areas'])->pluck('id')->toArray();
            if (!empty($areasIds)) {
                $mentorProfile->areasInteres()->sync($areasIds);
            }

            // Crear reseñas (mínimo 3 por mentor)
            foreach ($mentorData['reviews'] as $index => $reviewData) {
                // Rotar entre estudiantes demo para variedad
                $studentUser = $demoStudents[$index % $demoStudents->count()];

                MentorReview::updateOrCreate(
                    [
                        'mentor_id' => $mentorProfile->id,
                        'user_id' => $studentUser->id,
                    ],
                    [
                        'rating' => $reviewData['rating'],
                        'comment' => $reviewData['comment'],
                        'addressed_interests' => $reviewData['addressed_interests'] ?? 'yes',
                        'interests_clarity' => $reviewData['interests_clarity'] ?? 5,
                    ]
                );
            }

            // Recalcular calificación promedio basado en reviews
            $mentorProfile->updateAverageRating();

            $this->command->info("✓ Mentor creado: {$user->name} ({$user->email}) - Rating: {$mentorProfile->calificacionPromedio}");
        }

        $this->command->info('✓ Se crearon 8 mentores demo con perfiles completos, reseñas y email verificado');
    }
}
