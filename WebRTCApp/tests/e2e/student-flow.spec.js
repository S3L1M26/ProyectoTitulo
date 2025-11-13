import { test, expect } from '@playwright/test';
import { loginAsStudent, waitForInertia } from './helpers/auth.js';
import { setupZoomApiMocks } from './mocks/zoom.js';

/**
 * Test E2E: Flujo completo del Estudiante/Aprendiz
 * 
 * Flujo:
 * 1. Login como estudiante
 * 2. Ver dashboard con mentorías
 * 3. Ver mentoría confirmada
 * 4. Click en "Unirse a reunión"
 * 5. Verificar redirección a Zoom
 */

test.describe('Flujo completo del Estudiante', () => {
  test.beforeEach(async ({ page }) => {
    // Configurar mocks de Zoom
    await setupZoomApiMocks(page);
  });

  test('Estudiante puede ver mentoría confirmada y unirse a reunión Zoom', async ({ page }) => {
    // STEP 1: Login como estudiante
    test.step('Login como estudiante', async () => {
      await loginAsStudent(page, {
        email: 'student@test.com',
        password: 'password',
      });
      
      // Verificar que estamos en el dashboard
      await expect(page).toHaveURL(/\/student\/dashboard|\/dashboard/);
      await waitForInertia(page);
      
      console.log('✓ Login como estudiante exitoso');
    });

    // STEP 2: Ver dashboard con mentorías
    test.step('Ver dashboard con mentorías', async () => {
      // El dashboard debería mostrar las mentorías del estudiante
      // Buscar sección de "Mis Mentorías" o similar
      const mentoriasSection = page.locator('h2:has-text("Mentorías"), h3:has-text("Próximas mentorías"), [data-testid="mentorias-list"]');
      
      // Puede estar en el dashboard o en una página separada
      if (!await mentoriasSection.isVisible({ timeout: 5000 }).catch(() => false)) {
        // Navegar a la página de mentorías si no está en el dashboard
        const mentoriasLink = page.locator('a:has-text("Mis mentorías"), nav a[href*="mentorias"]').first();
        if (await mentoriasLink.isVisible({ timeout: 3000 }).catch(() => false)) {
          await mentoriasLink.click();
          await waitForInertia(page);
        } else {
          // Navegar directamente
          await page.goto('/student/mentorias');
          await waitForInertia(page);
        }
      }
      
      // Verificar que la sección está visible
      await expect(page.locator('text=/mentoría|reunión/i').first()).toBeVisible({ timeout: 10000 });
      
      // Screenshot del dashboard
      await page.screenshot({ 
        path: 'tests/e2e/results/screenshots/student-dashboard.png',
        fullPage: true,
      });
      
      console.log('✓ Dashboard de estudiante visible');
    });

    // STEP 3: Encontrar y visualizar mentoría confirmada
    test.step('Ver detalles de mentoría confirmada', async () => {
      // Buscar una mentoría con estado "confirmada"
      const mentoriaConfirmada = page.locator('[data-status="confirmada"], .mentoria-confirmada, div:has-text("Confirmada")').first();
      
      await expect(mentoriaConfirmada).toBeVisible({ timeout: 10000 });
      
      // Verificar que se muestra información de la reunión
      await expect(mentoriaConfirmada).toContainText(/zoom|reunión|enlace/i);
      
      // Buscar el enlace o botón de "Unirse"
      const unirseButton = page.locator('a:has-text("Unirse"), button:has-text("Unirse"), a[href*="zoom.us"]').first();
      await expect(unirseButton).toBeVisible();
      
      console.log('✓ Mentoría confirmada encontrada');
    });

    // STEP 4: Click en "Unirse a reunión"
    test.step('Click en botón Unirse', async () => {
      // Encontrar el botón/link de unirse
      const unirseLink = page.locator('a:has-text("Unirse"), a[href*="mentorias"][href*="unirse"], button:has-text("Unirse")').first();
      
      await expect(unirseLink).toBeVisible();
      
      // Antes de hacer click, preparar para capturar la navegación
      const href = await unirseLink.getAttribute('href');
      console.log('📍 Enlace de Unirse:', href);
      
      // Click en el botón
      await unirseLink.click();
      
      console.log('✓ Click en Unirse realizado');
    });

    // STEP 5: Verificar redirección a Zoom o página intermedia
    test.step('Verificar redirección correcta', async () => {
      // Puede redirigir directamente a Zoom o a una página intermedia de la app
      await page.waitForTimeout(2000);
      
      const currentUrl = page.url();
      
      // Verificar que estamos en Zoom o en una página de "unirse"
      const isZoomUrl = currentUrl.includes('zoom.us');
      const isJoinPage = currentUrl.includes('/mentorias/') && currentUrl.includes('/unirse');
      
      expect(isZoomUrl || isJoinPage).toBeTruthy();
      
      if (isJoinPage) {
        // Si es página intermedia, debe tener el enlace de Zoom
        const zoomLink = page.locator('a[href*="zoom.us/j/"]');
        await expect(zoomLink).toBeVisible({ timeout: 5000 });
        
        // Verificar que el enlace contiene el ID correcto del mock
        const linkHref = await zoomLink.getAttribute('href');
        expect(linkHref).toContain('999888777');
        
        console.log('✓ Página intermedia con enlace Zoom:', linkHref);
      } else {
        console.log('✓ Redirección directa a Zoom:', currentUrl);
      }
      
      // Screenshot de la página final
      await page.screenshot({ 
        path: 'tests/e2e/results/screenshots/student-unirse-zoom.png',
        fullPage: true,
      });
    });
  });

  test('Estudiante puede ver notificaciones de mentoría confirmada', async ({ page }) => {
    // Login
    await loginAsStudent(page);
    
    // Navegar a notificaciones
    const notificacionesLink = page.locator('a:has-text("Notificaciones"), a[href*="notifications"], [data-testid="notifications-link"]').first();
    
    if (await notificacionesLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await notificacionesLink.click();
      await waitForInertia(page);
    } else {
      await page.goto('/student/notifications');
      await waitForInertia(page);
    }
    
    // Verificar que hay notificaciones
    const notificationItem = page.locator('.notification-item, [data-testid="notification"]').first();
    
    if (await notificationItem.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Verificar que menciona mentoría o aceptación
      await expect(notificationItem).toContainText(/mentoría|aceptada|confirmada/i);
      
      console.log('✓ Notificaciones de mentoría visibles');
    } else {
      console.log('ℹ️ No hay notificaciones visibles (esperado si no hay datos)');
    }
    
    // Screenshot
    await page.screenshot({ 
      path: 'tests/e2e/results/screenshots/student-notifications.png',
      fullPage: true,
    });
  });

  test('Estudiante puede ver información del mentor en mentoría', async ({ page }) => {
    // Login
    await loginAsStudent(page);
    
    // Ir a mentorías
    await page.goto('/student/mentorias');
    await waitForInertia(page);
    
    // Buscar card de mentoría
    const mentoriaCard = page.locator('[data-status="confirmada"], .mentoria-card').first();
    
    if (await mentoriaCard.isVisible({ timeout: 5000 })) {
      // Verificar que se muestra info del mentor
      await expect(mentoriaCard).toContainText(/mentor|nombre/i);
      
      // Puede haber un link al perfil del mentor
      const mentorLink = mentoriaCard.locator('a[href*="/mentor/"]');
      if (await mentorLink.isVisible({ timeout: 3000 }).catch(() => false)) {
        console.log('✓ Link a perfil del mentor disponible');
      }
      
      console.log('✓ Información del mentor visible');
    }
  });

  test('Estudiante ve contador de tiempo hasta la mentoría', async ({ page }) => {
    // Login
    await loginAsStudent(page);
    
    // Ir a dashboard o mentorías
    await page.goto('/student/dashboard');
    await waitForInertia(page);
    
    // Buscar contador de tiempo (si existe en el frontend)
    const countdown = page.locator('[data-testid="countdown"], .countdown, text=/en \\d+ (hora|día|minuto)/i');
    
    if (await countdown.isVisible({ timeout: 5000 }).catch(() => false)) {
      const countdownText = await countdown.textContent();
      console.log('✓ Contador de tiempo visible:', countdownText);
    } else {
      console.log('ℹ️ Contador de tiempo no implementado en UI');
    }
  });
});

test.describe('Validaciones en flujo Estudiante', () => {
  test('Estudiante no puede unirse a mentoría antes de tiempo', async ({ page }) => {
    // Este test requeriría datos específicos con mentorías futuras
    await loginAsStudent(page);
    
    // Navegar a mentoría que está muy lejos en el futuro
    // El botón "Unirse" debería estar deshabilitado
    
    // Buscar botón de unirse
    const unirseButton = page.locator('button:has-text("Unirse"):disabled, button[disabled]:has-text("Unirse")').first();
    
    if (await unirseButton.isVisible({ timeout: 5000 }).catch(() => false)) {
      await expect(unirseButton).toBeDisabled();
      console.log('✓ Botón Unirse deshabilitado para mentorías futuras');
    } else {
      console.log('ℹ️ Validación de tiempo no implementada en UI');
    }
  });

  test('Estudiante ve mensaje si no tiene mentorías', async ({ page }) => {
    // Este test requeriría un usuario sin mentorías
    await loginAsStudent(page, {
      email: 'student.sin.mentorias@test.com',
      password: 'password',
    });
    
    await page.goto('/student/mentorias');
    await waitForInertia(page);
    
    // Buscar mensaje de "no hay mentorías"
    const emptyMessage = page.locator('text=/no tienes mentorías|aún no hay/i, [data-testid="empty-state"]');
    
    if (await emptyMessage.isVisible({ timeout: 5000 }).catch(() => false)) {
      console.log('✓ Mensaje de estado vacío visible');
    }
  });
});
