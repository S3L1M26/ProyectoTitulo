#!/bin/bash

# Script para preparar la base de datos de E2E tests
# Ejecuta migraciones y seeders en la base de datos webrtc_testing

echo "🧪 Preparando base de datos de E2E (webrtc_testing)..."

# Limpiar caché de configuración
php artisan config:clear

# Obtener el nombre de la conexión de testing desde .env
TESTING_CONN=$(grep DB_TESTING_CONNECTION .env | cut -d '=' -f2)
TESTING_CONN=${TESTING_CONN:-testing}

echo "📡 Usando conexión: $TESTING_CONN"

# Ejecutar migraciones fresh especificando la base de datos
php artisan migrate:fresh --database=$TESTING_CONN --force

# Ejecutar seeder especificando la base de datos  
php artisan db:seed --database=$TESTING_CONN --class=E2ETestSeeder --force

echo "✅ Base de datos de E2E lista!"
echo ""
echo "📧 Usuarios de prueba:"
echo "   Mentor: mentor@test.com / password"
echo "   Estudiante: student@test.com / password"
