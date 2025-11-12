import { defineConfig, devices } from '@playwright/test';

/**
 * Configuración de Playwright para tests E2E
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  // Directorio donde están los tests E2E
  testDir: './tests/e2e',
  
  // Timeout global para cada test
  timeout: 60 * 1000, // 60 segundos
  
  // Expect timeout para aserciones
  expect: {
    timeout: 10 * 1000, // 10 segundos
  },
  
  // Configuración de retry en caso de fallo
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  
  // Número de workers para ejecución paralela
  workers: process.env.CI ? 1 : undefined,
  
  // Reporter: list en local, html en CI
  reporter: [
    ['list'],
    ['html', { outputFolder: 'tests/e2e/results/html' }],
    ['json', { outputFile: 'tests/e2e/results/results.json' }],
  ],
  
  // Configuración compartida para todos los proyectos
  use: {
    // URL base de la aplicación
    baseURL: process.env.BASE_URL || process.env.APP_URL || 'http://localhost:8000',
    
    // Captura de traza en caso de fallo
    trace: 'on-first-retry',
    
    // Screenshot automático en fallo
    screenshot: 'only-on-failure',
    
    // Video en caso de fallo
    video: 'retain-on-failure',
    
    // Timeout para acciones (click, fill, etc.)
    actionTimeout: 15 * 1000,
    
    // User agent
    userAgent: 'Playwright E2E Tests',
    
    // Viewport por defecto
    viewport: { width: 1280, height: 720 },
    
    // Ignorar errores HTTPS en desarrollo
    ignoreHTTPSErrors: true,
    
    // Configuración de navegación
    navigationTimeout: 30 * 1000,
  },

  // Configuración de proyectos (navegadores)
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    // Tests en mobile
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },

    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  // Servidor de desarrollo (opcional)
  // webServer: {
  //   command: 'npm run dev',
  //   url: 'http://localhost:8000',
  //   reuseExistingServer: !process.env.CI,
  //   timeout: 120 * 1000,
  // },

  // Directorio de salida para screenshots y videos
  outputDir: 'tests/e2e/results/artifacts',
});
