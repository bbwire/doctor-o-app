import { defineConfig, devices } from '@playwright/test'

/**
 * Admin app E2E tests.
 * Prerequisites: API running (e.g. yarn dev:api from repo root) and admin app running (yarn dev).
 * Or run with webServer to start the admin app automatically (API must still be running).
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: 'e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3002',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure'
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } }
  ],
  webServer: process.env.CI
    ? undefined
    : {
        command: 'yarn dev',
        url: 'http://localhost:3002',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000
      }
})
