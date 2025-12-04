<?php

namespace Database\Seeders;

use App\Models\AreaInteres;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreasInteresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['nombre' => 'Desarrollo Web Frontend', 'descripcion' => 'Especialización en tecnologías del lado cliente como HTML, CSS, JavaScript y frameworks modernos.', 'roadmap_url' => 'https://roadmap.sh/frontend'],
            ['nombre' => 'Desarrollo Web Backend', 'descripcion' => 'Desarrollo de aplicaciones del lado servidor, APIs y gestión de bases de datos.', 'roadmap_url' => 'https://roadmap.sh/backend'],
            ['nombre' => 'Full Stack', 'descripcion' => 'Dominio de frontend y backend para construir aplicaciones end-to-end.', 'roadmap_url' => 'https://roadmap.sh/full-stack'],
            ['nombre' => 'Desarrollo Mobile', 'descripcion' => 'Creación de aplicaciones móviles nativas e híbridas para iOS y Android.', 'roadmap_url' => 'https://roadmap.sh/android'],
            ['nombre' => 'DevOps', 'descripcion' => 'Integración y despliegue continuo, automatización de infraestructura y operaciones.', 'roadmap_url' => 'https://roadmap.sh/devops'],
            ['nombre' => 'Ciberseguridad', 'descripcion' => 'Protección de sistemas, redes y datos contra amenazas digitales y vulnerabilidades.', 'roadmap_url' => 'https://roadmap.sh/cyber-security'],
            ['nombre' => 'Inteligencia Artificial', 'descripcion' => 'Desarrollo de sistemas inteligentes, machine learning y procesamiento de datos.', 'roadmap_url' => 'https://roadmap.sh/ai-engineer'],
            ['nombre' => 'Análisis de Datos', 'descripcion' => 'Extracción, procesamiento y análisis de grandes volúmenes de información para toma de decisiones.', 'roadmap_url' => 'https://roadmap.sh/data-analyst'],
            ['nombre' => 'Data Engineer', 'descripcion' => 'Construcción de pipelines y plataformas de datos escalables.', 'roadmap_url' => 'https://roadmap.sh/data-engineer'],
            ['nombre' => 'Cloud Computing', 'descripcion' => 'Servicios en la nube, arquitecturas escalables y migración de infraestructura.'],
            ['nombre' => 'Administración de Sistemas', 'descripcion' => 'Gestión y mantenimiento de servidores, redes y infraestructura tecnológica.'],
            ['nombre' => 'Testing y QA', 'descripcion' => 'Pruebas de software, control de calidad y automatización de testing.', 'roadmap_url' => 'https://roadmap.sh/qa'],
            ['nombre' => 'UI/UX Design', 'descripcion' => 'Diseño de interfaces de usuario y experiencia de usuario centrada en usabilidad.', 'roadmap_url' => 'https://roadmap.sh/ux-design'],
            ['nombre' => 'Blockchain', 'descripcion' => 'Desarrollo de aplicaciones descentralizadas y tecnologías de registro distribuido.', 'roadmap_url' => 'https://roadmap.sh/blockchain'],
            ['nombre' => 'IoT (Internet of Things)', 'descripcion' => 'Desarrollo de soluciones para dispositivos conectados y sistemas embebidos.'],
            ['nombre' => 'Arquitectura de Software', 'descripcion' => 'Diseño de sistemas escalables, patrones arquitectónicos y mejores prácticas.', 'roadmap_url' => 'https://roadmap.sh/software-architect'],
            ['nombre' => 'Gestión de Proyectos TI', 'descripcion' => 'Planificación, coordinación y liderazgo de proyectos tecnológicos y equipos de desarrollo.', 'roadmap_url' => 'https://roadmap.sh/product-manager'],
            ['nombre' => 'PostgreSQL DBA', 'descripcion' => 'Administración experta de bases de datos PostgreSQL.', 'roadmap_url' => 'https://roadmap.sh/postgresql-dba'],
            ['nombre' => 'iOS', 'descripcion' => 'Desarrollo de aplicaciones para el ecosistema Apple iOS.', 'roadmap_url' => 'https://roadmap.sh/ios'],
            ['nombre' => 'Machine Learning', 'descripcion' => 'Aplicación de algoritmos de aprendizaje para resolver problemas.', 'roadmap_url' => 'https://roadmap.sh/machine-learning'],
            ['nombre' => 'Tech Writer', 'descripcion' => 'Redacción técnica para documentación de productos y APIs.', 'roadmap_url' => 'https://roadmap.sh/technical-writer'],
            ['nombre' => 'Game Developer', 'descripcion' => 'Desarrollo de videojuegos en distintas plataformas.', 'roadmap_url' => 'https://roadmap.sh/game-developer'],
            ['nombre' => 'Server-side Game Developer', 'descripcion' => 'Backends y servidores para juegos online.', 'roadmap_url' => 'https://roadmap.sh/server-side-game-developer'],
            ['nombre' => 'MLOps', 'descripcion' => 'Operacionalización de modelos de ML en producción.', 'roadmap_url' => 'https://roadmap.sh/mlops'],
            ['nombre' => 'Engineering Manager', 'descripcion' => 'Liderazgo técnico y gestión de equipos de ingeniería.', 'roadmap_url' => 'https://roadmap.sh/engineering-manager'],
            ['nombre' => 'Developer Relations', 'descripcion' => 'Relación con desarrolladores y evangelización técnica.', 'roadmap_url' => 'https://roadmap.sh/devrel'],
            ['nombre' => 'BI Analyst', 'descripcion' => 'Análisis de negocio con inteligencia de datos.', 'roadmap_url' => 'https://roadmap.sh/bi-analyst'],
        ];

        // Crear cada área de interés usando updateOrCreate para evitar duplicados
        foreach ($areas as $area) {
            AreaInteres::updateOrCreate(
                ['nombre' => $area['nombre']],
                [
                    'descripcion' => $area['descripcion'],
                    'roadmap_url' => $area['roadmap_url'] ?? null,
                ]
            );
        }
    }
}
