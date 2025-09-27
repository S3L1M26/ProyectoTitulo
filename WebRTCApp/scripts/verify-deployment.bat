@echo off
REM ===========================================
REM DEPLOYMENT VERIFICATION SCRIPT - WINDOWS
REM ===========================================

echo 🔍 VERIFICANDO DEPLOYMENT DE WEBRTCAPP...
echo =========================================

set DOMAIN=%1
if "%DOMAIN%"=="" set DOMAIN=localhost

set PORT=%2
if "%PORT%"=="" set PORT=80

echo.
echo 🐳 Verificando contenedores Docker...
echo -----------------------------------

REM Check if Docker is available
docker --version >nul 2>&1
if errorlevel 1 (
    echo ⚠️  Docker no disponible
) else (
    echo ✅ Docker disponible
    docker ps --filter "name=webrtcapp" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
)

echo.
echo 🌐 Verificando servidor web...
echo ----------------------------

REM Check if web server responds (fix curl command for Windows)
curl -s -o nul -w "%%{http_code}" "http://%DOMAIN%:%PORT%" >temp_status.txt 2>nul
if exist temp_status.txt (
    set /p HTTP_STATUS=<temp_status.txt
    del temp_status.txt
    
    if "%HTTP_STATUS%"=="200" (
        echo ✅ Servidor web respondiendo (HTTP %HTTP_STATUS%^)
    ) else (
        echo ❌ Servidor web no responde (HTTP %HTTP_STATUS%^)
    )
) else (
    echo ⚠️  No se pudo probar el servidor web (curl no disponible^)
)

echo.
echo 🐘 Verificando aplicación Laravel...
echo ----------------------------------

REM Check if Laravel container is running
docker ps --filter "name=webrtcapp-app" --format "{{.Names}}" 2>nul | findstr "webrtcapp-app" >nul
if errorlevel 1 (
    echo ⚠️  Contenedor Laravel no encontrado
) else (
    echo ✅ Contenedor Laravel ejecutándose
    
    REM Check Laravel version (fix command)
    echo 📦 Obteniendo versión Laravel...
    docker exec webrtcapp-app php artisan --version >temp_version.txt 2>nul
    if exist temp_version.txt (
        set /p LARAVEL_VERSION=<temp_version.txt
        echo 📦 Versión Laravel: %LARAVEL_VERSION%
        del temp_version.txt
    ) else (
        echo ⚠️  No se pudo obtener la versión de Laravel
    )
)

echo.
echo 🎨 Verificando assets compilados...
echo --------------------------------

if exist "public\build" (
    echo ✅ Directorio build existe
    
    REM Simple check for files
    if exist "public\build\*.js" (
        echo ✅ Archivos JS encontrados
    ) else (
        echo ⚠️  No se encontraron archivos JS
    )
    
    if exist "public\build\*.css" (
        echo ✅ Archivos CSS encontrados
    ) else (
        echo ⚠️  No se encontraron archivos CSS
    )
    
    REM Show manifest if exists
    if exist "public\build\manifest.json" (
        echo ✅ Manifest.json existe
    ) else (
        echo ⚠️  Manifest.json no encontrado
    )
) else (
    echo ❌ Directorio build no encontrado
)

echo.
echo 📊 RESUMEN DEL DEPLOYMENT
echo ========================
echo 🌍 Domain: %DOMAIN%:%PORT%
echo 📅 Verificado: %date% %time%
echo.
echo ✅ = Funcionando correctamente
echo ⚠️  = Funcionando pero puede mejorarse  
echo ❌ = Problema que requiere atención
echo.
echo 🚀 Verificación de deployment completada!
echo.

pause