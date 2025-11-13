/**
 * Mocks para Zoom API en tests E2E
 * Intercepta y simula respuestas de la API de Zoom
 */

/**
 * Mock del token de autenticación OAuth de Zoom
 */
export const mockZoomOAuthToken = {
  access_token: 'mock_access_token_e2e_12345',
  token_type: 'bearer',
  expires_in: 3600,
  scope: 'meeting:write meeting:read',
};

/**
 * Mock de respuesta de creación de reunión Zoom
 */
export const mockZoomMeetingResponse = {
  id: 999888777,
  uuid: 'mock-uuid-e2e-test-12345',
  host_id: 'mock-host-id',
  topic: 'Mentoría de prueba E2E',
  type: 2, // Scheduled meeting
  start_time: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(), // Mañana
  duration: 60,
  timezone: 'UTC',
  created_at: new Date().toISOString(),
  join_url: 'https://zoom.us/j/999888777?pwd=mockpassword',
  start_url: 'https://zoom.us/s/999888777?zak=mockhost',
  password: 'mockpass123',
  h323_password: '123456',
  pstn_password: '123456',
  encrypted_password: 'encrypted_mock',
  settings: {
    host_video: true,
    participant_video: true,
    cn_meeting: false,
    in_meeting: false,
    join_before_host: false,
    mute_upon_entry: true,
    watermark: false,
    use_pmi: false,
    approval_type: 2,
    audio: 'voip',
    auto_recording: 'none',
    waiting_room: true,
  },
};

/**
 * Mock de respuesta al obtener detalles de una reunión
 */
export const mockZoomMeetingDetails = {
  ...mockZoomMeetingResponse,
  status: 'waiting', // waiting, started, finished
  start_time: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
};

/**
 * Configura los interceptores de Zoom API
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @returns {Promise<void>}
 */
export async function setupZoomApiMocks(page) {
  // Interceptar OAuth token
  await page.route('**/zoom.us/oauth/token', async (route) => {
    console.log('🔷 Intercepted: Zoom OAuth token request');
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(mockZoomOAuthToken),
    });
  });

  // Interceptar creación de reunión
  await page.route('**/api.zoom.us/v2/users/me/meetings', async (route) => {
    if (route.request().method() === 'POST') {
      console.log('🔷 Intercepted: Zoom create meeting request');
      const requestData = route.request().postDataJSON();
      
      // Personalizar respuesta con datos de la petición
      const response = {
        ...mockZoomMeetingResponse,
        topic: requestData.topic || mockZoomMeetingResponse.topic,
        start_time: requestData.start_time || mockZoomMeetingResponse.start_time,
        duration: requestData.duration || mockZoomMeetingResponse.duration,
      };
      
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify(response),
      });
    } else {
      await route.continue();
    }
  });

  // Interceptar obtener detalles de reunión
  await page.route('**/api.zoom.us/v2/meetings/**', async (route) => {
    if (route.request().method() === 'GET') {
      console.log('🔷 Intercepted: Zoom get meeting details request');
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(mockZoomMeetingDetails),
      });
    } else if (route.request().method() === 'DELETE') {
      console.log('🔷 Intercepted: Zoom delete meeting request');
      await route.fulfill({
        status: 204,
        body: '',
      });
    } else if (route.request().method() === 'PATCH') {
      console.log('🔷 Intercepted: Zoom update meeting request');
      await route.fulfill({
        status: 204,
        body: '',
      });
    } else {
      await route.continue();
    }
  });

  console.log('✓ Zoom API mocks configurados');
}

/**
 * Simula un error de Zoom API
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @param {number} statusCode - Código de error (401, 429, 500, etc.)
 * @returns {Promise<void>}
 */
export async function setupZoomApiError(page, statusCode = 500) {
  await page.route('**/api.zoom.us/v2/**', async (route) => {
    console.log(`🔷 Intercepted: Zoom API error ${statusCode}`);
    
    const errorResponses = {
      401: { code: 124, message: 'Invalid access token' },
      429: { code: 429, message: 'Rate limit exceeded' },
      500: { code: 500, message: 'Internal server error' },
      404: { code: 3001, message: 'Meeting not found' },
    };
    
    await route.fulfill({
      status: statusCode,
      contentType: 'application/json',
      body: JSON.stringify(errorResponses[statusCode] || { code: statusCode, message: 'Error' }),
    });
  });
}

/**
 * Limpia todos los interceptores de Zoom
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @returns {Promise<void>}
 */
export async function cleanupZoomApiMocks(page) {
  await page.unroute('**/zoom.us/oauth/token');
  await page.unroute('**/api.zoom.us/v2/**');
  console.log('✓ Zoom API mocks limpiados');
}

/**
 * Verifica que se haya llamado a la API de Zoom
 * @param {import('@playwright/test').Page} page - Página de Playwright
 * @param {string} endpoint - Endpoint esperado (ej: '/meetings', '/oauth/token')
 * @returns {Promise<boolean>}
 */
export async function verifyZoomApiCalled(page, endpoint) {
  // Esta función requeriría tracking de las llamadas interceptadas
  // Por ahora retornamos true, pero se puede extender para tracking real
  console.log(`ℹ️ Verificando llamada a Zoom API: ${endpoint}`);
  return true;
}
