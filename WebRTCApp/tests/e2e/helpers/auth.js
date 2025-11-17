import { expect } from '@playwright/test';

/**
 * Helper para autenticación de usuarios en tests E2E
 */

/**
 * Realiza login como mentor
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @param {Object} credentials - Credenciales del mentor
 * @returns {Promise<void>}
 */
export async function loginAsMentor(page, credentials = null) {
  const defaultCredentials = {
    email: credentials?.email || 'mentor@test.com',
    password: credentials?.password || 'password',
  };

  // Ir a la página de login
  await page.goto('/login?role=mentor', { waitUntil: 'commit', timeout: 90000 });
  
  // Pequeña pausa para asegurar que la página se estabilice
  await page.waitForTimeout(2000);
  
  // Esperar a que el formulario esté visible (aumentado a 60s por Vite dev server)
  await page.waitForSelector('input[name="email"]', { timeout: 60000 });
  
  // Llenar credenciales
  await page.fill('input[name="email"]', defaultCredentials.email);
  await page.fill('input[name="password"]', defaultCredentials.password);
  
  // Submit form
  await page.click('button[type="submit"]');
  
  // Esperar redirección al dashboard
  await page.waitForURL(/\/mentor\/dashboard|\/dashboard/, { timeout: 10000 });
  
  // Verificar que el login fue exitoso
  await expect(page.locator('body')).not.toContainText('Invalid credentials');
  
  console.log('✓ Login como mentor exitoso');
}

/**
 * Realiza login como estudiante/aprendiz
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @param {Object} credentials - Credenciales del estudiante
 * @returns {Promise<void>}
 */
export async function loginAsStudent(page, credentials = null) {
  const defaultCredentials = {
    email: credentials?.email || 'student@test.com',
    password: credentials?.password || 'password',
  };

  // Ir a la página de login
  await page.goto('/login?role=student', { waitUntil: 'commit', timeout: 90000 });
  
  // Pequeña pausa para asegurar que la página se estabilice
  await page.waitForTimeout(2000);
  
  // Esperar a que el formulario esté visible (aumentado a 60s por Vite dev server)
  await page.waitForSelector('input[name="email"]', { timeout: 60000 });
  
  // Llenar credenciales
  await page.fill('input[name="email"]', defaultCredentials.email);
  await page.fill('input[name="password"]', defaultCredentials.password);
  
  // Submit form
  await page.click('button[type="submit"]');
  
  // Esperar redirección al dashboard
  await page.waitForURL(/\/student\/dashboard|\/dashboard/, { timeout: 10000 });
  
  // Verificar que el login fue exitoso
  await expect(page.locator('body')).not.toContainText('Invalid credentials');
  
  console.log('✓ Login como estudiante exitoso');
}

/**
 * Realiza logout
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @returns {Promise<void>}
 */
export async function logout(page) {
  // Buscar el botón/link de logout (puede variar según el diseño)
  const logoutButton = page.locator('button:has-text("Cerrar sesión"), a:has-text("Cerrar sesión"), form[action*="logout"] button');
  
  if (await logoutButton.isVisible()) {
    await logoutButton.click();
    await page.waitForURL('/login', { timeout: 5000 });
    console.log('✓ Logout exitoso');
  }
}

/**
 * Guarda el estado de autenticación para reutilizar
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @param {string} statePath - Ruta donde guardar el estado
 * @returns {Promise<void>}
 */
export async function saveAuthState(page, statePath) {
  await page.context().storageState({ path: statePath });
  console.log(`✓ Estado de autenticación guardado en ${statePath}`);
}

/**
 * Verifica si el usuario está autenticado
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @returns {Promise<boolean>}
 */
export async function isAuthenticated(page) {
  // Ir a la página principal
  await page.goto('/');
  
  // Si redirige a login, no está autenticado
  const url = page.url();
  return !url.includes('/login');
}

/**
 * Espera a que la aplicación Inertia.js esté lista
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @returns {Promise<void>}
 */
export async function waitForInertia(page) {
  // Esperar a que Inertia haya cargado completamente
  await page.waitForFunction(() => {
    return window.history.state && window.history.state.page;
  }, { timeout: 10000 });
  
  // Pequeña pausa adicional para asegurar que React ha renderizado
  await page.waitForTimeout(500);
}
