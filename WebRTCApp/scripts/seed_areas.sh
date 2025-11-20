#!/bin/sh
set -e

echo "-> Waiting for DB (up to ~60s)..."
php -r 'for($i=0;$i<30;$i++){try{$pdo=new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE").";charset=utf8mb4",getenv("DB_USERNAME"),getenv("DB_PASSWORD"),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);echo "db ready\n";exit(0);}catch(Throwable $e){echo "waiting DB...\n";sleep(2);} } echo "db unreachable\n"; exit(1);'

echo "-> Running AreasInteresSeeder"
php artisan db:seed --class=AreasInteresSeeder --force --verbose || {
  echo "Seeder command failed" >&2
  exit 1
}

echo "-> Verifying AreaInteres count"
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class); $kernel->bootstrap(); echo "AreaInteres::count(): " . \App\\Models\\AreaInteres::count() . PHP_EOL;'

echo "-> Done"
