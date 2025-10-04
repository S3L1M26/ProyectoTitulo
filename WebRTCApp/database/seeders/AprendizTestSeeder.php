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
                'objetivos' => 'Mi objetivo principal es aprender desarrollo web full-stack con Laravel y React. Quiero especializarme en el desarrollo de aplicaciones modernas y escalables, mejorar mis habilidades en bases de datos y obtener experiencia práctica en proyectos reales.'
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
                'experiencia' => 'Tengo más de 8 años de experiencia en desarrollo de software, especializado en aplicaciones web modernas y arquitecturas escalables. He trabajado en empresas tecnológicas líderes y he mentoreado a más de 20 desarrolladores junior.',
                'biografia' => 'Soy un desarrollador full-stack apasionado por la enseñanza y el crecimiento profesional. Mi objetivo es ayudar a los nuevos desarrolladores a acelerar su carrera profesional compartiendo conocimientos prácticos y mejores prácticas de la industria.',
                'años_experiencia' => 8,
                'disponibilidad' => 'Lunes a Viernes: 18:00-21:00, Sábados: 09:00-12:00',
                'disponibilidad_detalle' => 'Prefiero sesiones de 1-2 horas por videollamada. Flexible con horarios para estudiantes que trabajan. Disponible para consultas urgentes vía mensaje.',
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
                'name' => 'Ana Frontend',
                'semestre' => 4,
                'objetivos' => 'Quiero especializarme en React y diseño de interfaces de usuario modernas.',
                'areas' => ['Desarrollo Web Frontend', 'Diseño UX/UI']
            ],
            [
                'email' => 'carlos.backend@example.com', 
                'name' => 'Carlos Backend',
                'semestre' => 6,
                'objetivos' => 'Mi meta es dominar arquitecturas de microservicios y APIs escalables.',
                'areas' => ['Desarrollo Web Backend', 'DevOps']
            ],
            [
                'email' => 'maria.data@example.com',
                'name' => 'María Data Science',
                'semestre' => 7,
                'objetivos' => 'Busco aprender machine learning aplicado a problemas reales de negocio.',
                'areas' => ['Análisis de Datos', 'Inteligencia Artificial']
            ],
            [
                'email' => 'luis.mobile@example.com',
                'name' => 'Luis Mobile',
                'semestre' => 3,
                'objetivos' => 'Quiero crear aplicaciones móviles innovadoras para iOS y Android.',
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
                'name' => 'Pedro Senior Dev',
                'experiencia' => 'Senior Developer con 12 años en la industria. Especialista en arquitecturas escalables y liderazgo técnico. He participado en múltiples startups y empresas Fortune 500.',
                'biografia' => 'Apasionado por enseñar y ver crecer a nuevos talentos. Creo en el aprendizaje práctico y el mentoring personalizado para acelerar carreras en tech.',
                'anos_experiencia' => 12,
                'disponibilidad' => 'Martes y Jueves: 19:00-21:00',
                'detalle' => 'Sesiones estructuradas de 90 minutos. Enfoque en arquitectura y best practices.',
                'rating' => 4.9,
                'disponible' => true,
                'areas' => ['Desarrollo Web Backend', 'DevOps', 'Arquitectura de Software']
            ],
            [
                'email' => 'laura.ui@example.com',
                'name' => 'Laura UX Designer',
                'experiencia' => 'UX/UI Designer con 6 años creando experiencias digitales excepcionales. Trabajo con metodologías de Design Thinking y prototipado rápido.',
                'biografia' => 'Mi misión es formar la próxima generación de diseñadores que pongan al usuario en el centro de cada decisión de producto.',
                'anos_experiencia' => 6,
                'disponibilidad' => 'Lunes, Miércoles, Viernes: 17:00-19:00',
                'detalle' => 'Sesiones prácticas con feedback en tiempo real sobre portfolios y proyectos.',
                'rating' => 4.7,
                'disponible' => true,
                'areas' => ['Diseño UX/UI', 'Desarrollo Web Frontend']
            ],
            [
                'email' => 'jorge.data@example.com',
                'name' => 'Jorge Data Scientist',
                'experiencia' => 'Data Scientist con 8 años analizando grandes volúmenes de datos y construyendo modelos predictivos para empresas globales.',
                'biografia' => 'Experto en transformar datos en insights accionables. Me encanta enseñar estadística aplicada y machine learning de forma práctica.',
                'anos_experiencia' => 8,
                'disponibilidad' => 'Fines de semana: 10:00-14:00',
                'detalle' => 'Sesiones intensivas de 2-3 horas con datasets reales y casos de estudio.',
                'rating' => 4.6,
                'disponible' => true,
                'areas' => ['Análisis de Datos', 'Inteligencia Artificial']
            ],
            [
                'email' => 'sofia.mobile@example.com',
                'name' => 'Sofía Mobile Expert',
                'experiencia' => 'Desarrolladora móvil con 7 años creando apps que han alcanzado millones de usuarios. Especialista en React Native y Flutter.',
                'biografia' => 'Creo que el futuro es móvil-first. Mi objetivo es formar desarrolladores que creen experiencias móviles increíbles.',
                'anos_experiencia' => 7,
                'disponibilidad' => 'Martes a Jueves: 20:00-22:00',
                'detalle' => 'Sesiones hands-on desarrollando apps reales desde cero hasta publicación.',
                'rating' => 4.8,
                'disponible' => true,
                'areas' => ['Desarrollo Mobile', 'Desarrollo Web Frontend']
            ],
            [
                'email' => 'ricardo.devops@example.com',
                'name' => 'Ricardo DevOps',
                'experiencia' => 'DevOps Engineer con 10 años automatizando infraestructuras y optimizando pipelines de CI/CD en entornos de alta disponibilidad.',
                'biografia' => 'La automatización y la cultura DevOps transforman equipos. Ayudo a developers a entender todo el ciclo de vida del software.',
                'anos_experiencia' => 10,
                'disponibilidad' => 'Lunes a Miércoles: 18:00-20:00',
                'detalle' => 'Sesiones técnicas con labs prácticos en AWS, Docker, Kubernetes.',
                'rating' => 4.5,
                'disponible' => false, // No disponible para testing
                'areas' => ['DevOps', 'Desarrollo Web Backend']
            ],
            [
                'email' => 'camila.junior@example.com',
                'name' => 'Camila Junior Mentor',
                'experiencia' => 'Desarrolladora Full-Stack con 3 años de experiencia. Recién empezando como mentora pero con mucho entusiasmo por ayudar.',
                'biografia' => 'Como junior que recientemente se convirtió en mentora, entiendo perfectamente los desafíos de empezar en tech.',
                'anos_experiencia' => 3,
                'disponibilidad' => 'Todos los días: 16:00-18:00',
                'detalle' => 'Sesiones casuales y flexibles. Enfoque en primeros pasos y proyectos personales.',
                'rating' => 4.2,
                'disponible' => true,
                'areas' => ['Desarrollo Web Frontend', 'Desarrollo Web Backend']
            ],
            [
                'email' => 'diego.freelance@example.com',
                'name' => 'Diego Freelancer',
                'experiencia' => 'Freelancer con 9 años desarrollando proyectos para clientes internacionales. Especialista en development remoto y gestión de proyectos.',
                'biografia' => 'Te enseño no solo a programar, sino a trabajar como freelancer exitoso y gestionar tu carrera independiente.',
                'anos_experiencia' => 9,
                'disponibilidad' => 'Horarios muy flexibles',
                'detalle' => 'Disponible según necesidades del estudiante. Enfoque en soft skills y business.',
                'rating' => 4.4,
                'disponible' => false, // Ocupado con proyectos
                'areas' => ['Desarrollo Web Frontend', 'Desarrollo Web Backend', 'Gestión de Proyectos']
            ],
            [
                'email' => 'valeria.startup@example.com',
                'name' => 'Valeria Startup CTO',
                'experiencia' => 'CTO y co-fundadora de 2 startups exitosas. 11 años liderando equipos técnicos y escalando productos desde MVP hasta millones de usuarios.',
                'biografia' => 'Mi pasión es formar la próxima generación de líderes técnicos. Enseño tanto código como estrategia de producto y liderazgo.',
                'anos_experiencia' => 11,
                'disponibilidad' => 'Sábados: 09:00-12:00',
                'detalle' => 'Sesiones de mentoría estratégica. Ideal para estudiantes con ambiciones emprendedoras.',
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
