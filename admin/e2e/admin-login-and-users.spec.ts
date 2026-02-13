import { test, expect } from '@playwright/test'

/**
 * E2E: Admin login and Users list.
 * Uses seeded admin user (see api/database/seeders/AdminUserSeeder.php).
 * Requires API running at NUXT_PUBLIC_API_BASE (default http://localhost:8000/api/v1).
 */
const ADMIN_EMAIL = process.env.ADMIN_TEST_EMAIL || 'admin@dro.com'
const ADMIN_PASSWORD = process.env.ADMIN_TEST_PASSWORD || '2succeeD?'

test.describe('Admin login and users list', () => {
  test('unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto('/users')
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByRole('heading', { name: /admin login/i })).toBeVisible()
  })

  test('login with invalid credentials shows error', async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel(/email/i).fill('wrong@example.com')
    await page.getByLabel(/password/i).fill('wrong')
    await page.getByRole('button', { name: /sign in/i }).click()
    await expect(page.getByText(/unable to sign in|check your credentials/i)).toBeVisible({ timeout: 5000 })
  })

  test('login as admin and open users list', async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel(/email/i).fill(ADMIN_EMAIL)
    await page.getByLabel(/password/i).fill(ADMIN_PASSWORD)
    await page.getByRole('button', { name: /sign in/i }).click()

    await expect(page).toHaveURL('/', { timeout: 10000 })
    await expect(page.getByRole('link', { name: /dashboard/i }).first()).toBeVisible()

    await page.goto('/users')
    await expect(page).toHaveURL('/users')
    await expect(page.getByRole('heading', { name: /^users$/i })).toBeVisible()
    await expect(page.getByText(/manage patients, doctors, and administrators/i)).toBeVisible()
    await expect(page.getByRole('button', { name: /create user/i })).toBeVisible()
  })
})
