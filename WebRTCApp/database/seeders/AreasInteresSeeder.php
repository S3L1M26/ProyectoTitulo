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
            ['nombre' => 'Desarrollo Web Frontend', 'descripcion' => 'Especialización en tecnologías del lado cliente como HTML, CSS, JavaScript y frameworks modernos.'],
            ['nombre' => 'Desarrollo Web Backend', 'descripcion' => 'Desarrollo de aplicaciones del lado servidor, APIs y gestión de bases de datos.'],
            ['nombre' => 'Desarrollo Mobile', 'descripcion' => 'Creación de aplicaciones móviles nativas e híbridas para iOS y Android.'],
            ['nombre' => 'DevOps', 'descripcion' => 'Integración y despliegue continuo, automatización de infraestructura y operaciones.'],
            ['nombre' => 'Ciberseguridad', 'descripcion' => 'Protección de sistemas, redes y datos contra amenazas digitales y vulnerabilidades.'],
            ['nombre' => 'Inteligencia Artificial', 'descripcion' => 'Desarrollo de sistemas inteligentes, machine learning y procesamiento de datos.'],
            ['nombre' => 'Análisis de Datos', 'descripcion' => 'Extracción, procesamiento y análisis de grandes volúmenes de información para toma de decisiones.'],
            ['nombre' => 'Cloud Computing', 'descripcion' => 'Servicios en la nube, arquitecturas escalables y migración de infraestructura.'],
            ['nombre' => 'Administración de Sistemas', 'descripcion' => 'Gestión y mantenimiento de servidores, redes y infraestructura tecnológica.'],
            ['nombre' => 'Testing y QA', 'descripcion' => 'Pruebas de software, control de calidad y automatización de testing.'],
            ['nombre' => 'UI/UX Design', 'descripcion' => 'Diseño de interfaces de usuario y experiencia de usuario centrada en usabilidad.'],
            ['nombre' => 'Blockchain', 'descripcion' => 'Desarrollo de aplicaciones descentralizadas y tecnologías de registro distribuido.'],
            ['nombre' => 'IoT (Internet of Things)', 'descripcion' => 'Desarrollo de soluciones para dispositivos conectados y sistemas embebidos.'],
            ['nombre' => 'Arquitectura de Software', 'descripcion' => 'Diseño de sistemas escalables, patrones arquitectónicos y mejores prácticas.'],
            ['nombre' => 'Gestión de Proyectos TI', 'descripcion' => 'Planificación, coordinación y liderazgo de proyectos tecnológicos y equipos de desarrollo.'],
        ];

        // Crear cada área de interés usando updateOrCreate para evitar duplicados
        foreach ($areas as $area) {
            AreaInteres::updateOrCreate(
                ['nombre' => $area['nombre']], // Condición de búsqueda
                ['descripcion' => $area['descripcion']] // Datos a actualizar/crear
            );
        }
    }
}
