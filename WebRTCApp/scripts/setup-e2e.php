#!/usr/bin/env php
<?php

/**
 * Script para preparar la base de datos E2E
 * Este script usa PHP puro para tener mejor control sobre las variables de entorno
 */

// Forzar la conexión de testing
putenv('DB_CONNECTION=testing');
$_ENV['DB_CONNECTION'] = 'testing';
$_SERVER['DB_CONNECTION'] = 'testing';

echo "🧪 Preparando base de datos de E2E (webrtc_testing)...\n";

// Limpiar caché de configuración
echo "🧹 Limpiando caché de configuración...\n";
passthru('php artisan config:clear');

// Ejecutar migraciones
echo "📦 Ejecutando migraciones fresh...\n";
passthru('php artisan migrate:fresh --force');

// Ejecutar seeder
echo "🌱 Ejecutando E2ETestSeeder...\n";
passthru('php artisan db:seed --class=E2ETestSeeder --force');

echo "\n✅ Base de datos de E2E lista!\n";
echo "\n📧 Usuarios de prueba:\n";
echo "   Mentor: mentor@test.com / password\n";
echo "   Estudiante: student@test.com / password\n";
