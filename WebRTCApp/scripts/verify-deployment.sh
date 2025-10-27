#!/bin/bash
# ===========================================
# DEPLOYMENT VERIFICATION SCRIPT
# ===========================================
# Verifica que todos los componentes estén funcionando correctamente

set -e

echo "🔍 VERIFICANDO DEPLOYMENT DE WEBRTCAPP..."
echo "========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DOMAIN=${1:-"localhost"}
PORT=${2:-"80"}
HEALTH_ENDPOINT="/health"

# ===========================================
# HELPER FUNCTIONS
# ===========================================
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1${NC}"
        exit 1
    fi
}

check_warning() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${YELLOW}⚠️  $1${NC}"
    fi
}

# ===========================================
# DOCKER CONTAINERS CHECK
# ===========================================
echo ""
echo "🐳 Verificando contenedores Docker..."
echo "-----------------------------------"

if command -v docker &> /dev/null; then
    # Check if containers are running
    docker ps --filter "name=webrtcapp" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    
    # Check app container health
    APP_HEALTH=$(docker inspect --format='{{.State.Health.Status}}' webrtcapp-app 2>/dev/null || echo "unknown")
    if [ "$APP_HEALTH" = "healthy" ]; then
        echo -e "${GREEN}✅ App container is healthy${NC}"
    else
        echo -e "${YELLOW}⚠️  App container health: $APP_HEALTH${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Docker not available${NC}"
fi

# ===========================================
# WEB SERVER CHECK
# ===========================================
echo ""
echo "🌐 Verificando servidor web..."
echo "----------------------------"

# Check if web server responds
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://${DOMAIN}:${PORT}" 2>/dev/null || echo "000")
if [ "$HTTP_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ Web server responding (HTTP $HTTP_STATUS)${NC}"
else
    echo -e "${RED}❌ Web server not responding (HTTP $HTTP_STATUS)${NC}"
fi

# Check health endpoint
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://${DOMAIN}:${PORT}${HEALTH_ENDPOINT}" 2>/dev/null || echo "000")
if [ "$HEALTH_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ Health check endpoint responding${NC}"
else
    echo -e "${YELLOW}⚠️  Health check endpoint not responding (HTTP $HEALTH_STATUS)${NC}"
fi

# ===========================================
# LARAVEL APPLICATION CHECK
# ===========================================
echo ""
echo "🐘 Verificando aplicación Laravel..."
echo "----------------------------------"

if command -v docker &> /dev/null && docker ps --filter "name=webrtcapp-app" | grep -q "Up"; then
    # Check Laravel version
    LARAVEL_VERSION=$(docker exec webrtcapp-app php artisan --version 2>/dev/null || echo "Unknown")
    echo "📦 Laravel version: $LARAVEL_VERSION"
    
    # Check if database is reachable
    docker exec webrtcapp-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" 2>/dev/null
    check_status "Database connection"
    
    # Check if cache is working
    docker exec webrtcapp-app php artisan cache:put test_key test_value 60 2>/dev/null
    docker exec webrtcapp-app php artisan cache:get test_key 2>/dev/null
    check_status "Cache functionality"
    
    # Check if queue is configured
    docker exec webrtcapp-app php artisan queue:work --stop-when-empty --timeout=1 2>/dev/null
    check_warning "Queue worker (optional)"
    
else
    echo -e "${YELLOW}⚠️  Laravel app container not found${NC}"
fi

# ===========================================
# ASSETS CHECK
# ===========================================
echo ""
echo "🎨 Verificando assets compilados..."
echo "--------------------------------"

# Check if build directory exists
if [ -d "public/build" ]; then
    echo -e "${GREEN}✅ Build directory exists${NC}"
    
    # Count built assets
    JS_COUNT=$(find public/build -name "*.js" | wc -l)
    CSS_COUNT=$(find public/build -name "*.css" | wc -l)
    echo "📁 JS files: $JS_COUNT"
    echo "📁 CSS files: $CSS_COUNT"
    
    if [ "$JS_COUNT" -gt 0 ] && [ "$CSS_COUNT" -gt 0 ]; then
        echo -e "${GREEN}✅ Assets compiled successfully${NC}"
    else
        echo -e "${YELLOW}⚠️  Few or no assets found${NC}"
    fi
else
    echo -e "${RED}❌ Build directory not found${NC}"
fi

# Check if assets are served correctly
ASSET_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://${DOMAIN}:${PORT}/build/manifest.json" 2>/dev/null || echo "000")
if [ "$ASSET_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ Asset manifest accessible${NC}"
else
    echo -e "${YELLOW}⚠️  Asset manifest not accessible (HTTP $ASSET_STATUS)${NC}"
fi

# ===========================================
# SECURITY CHECK
# ===========================================
echo ""
echo "🔒 Verificando configuración de seguridad..."
echo "-------------------------------------------"

# Check if HTTPS is available (if not localhost)
if [ "$DOMAIN" != "localhost" ] && [ "$DOMAIN" != "127.0.0.1" ]; then
    HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://${DOMAIN}" 2>/dev/null || echo "000")
    if [ "$HTTPS_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ HTTPS available${NC}"
    else
        echo -e "${YELLOW}⚠️  HTTPS not available (recommended for production)${NC}"
    fi
fi

# Check security headers
SECURITY_HEADERS=$(curl -s -I "http://${DOMAIN}:${PORT}" | grep -i "x-frame-options\|x-content-type-options\|strict-transport-security" | wc -l)
if [ "$SECURITY_HEADERS" -gt 0 ]; then
    echo -e "${GREEN}✅ Security headers present ($SECURITY_HEADERS found)${NC}"
else
    echo -e "${YELLOW}⚠️  Security headers missing${NC}"
fi

# ===========================================
# PERFORMANCE CHECK
# ===========================================
echo ""
echo "⚡ Verificando rendimiento..."
echo "--------------------------"

# Check response time
RESPONSE_TIME=$(curl -s -o /dev/null -w "%{time_total}" "http://${DOMAIN}:${PORT}" 2>/dev/null || echo "0")
if (( $(echo "$RESPONSE_TIME < 1.0" | bc -l 2>/dev/null || echo "0") )); then
    echo -e "${GREEN}✅ Response time: ${RESPONSE_TIME}s (good)${NC}"
elif (( $(echo "$RESPONSE_TIME < 3.0" | bc -l 2>/dev/null || echo "0") )); then
    echo -e "${YELLOW}⚠️  Response time: ${RESPONSE_TIME}s (acceptable)${NC}"
else
    echo -e "${RED}❌ Response time: ${RESPONSE_TIME}s (slow)${NC}"
fi

# Check if compression is enabled
COMPRESSION=$(curl -s -H "Accept-Encoding: gzip" -I "http://${DOMAIN}:${PORT}" | grep -i "content-encoding: gzip" | wc -l)
if [ "$COMPRESSION" -gt 0 ]; then
    echo -e "${GREEN}✅ Gzip compression enabled${NC}"
else
    echo -e "${YELLOW}⚠️  Gzip compression not detected${NC}"
fi

# ===========================================
# SUMMARY
# ===========================================
echo ""
echo "📊 RESUMEN DEL DEPLOYMENT"
echo "========================"
echo "🌍 Domain: $DOMAIN:$PORT"
echo "📅 Checked at: $(date)"
echo ""
echo "✅ = Funcionando correctamente"
echo "⚠️  = Funcionando pero puede mejorarse"
echo "❌ = Problema que requiere atención"
echo ""
echo "🚀 Deployment verification completed!"
echo ""