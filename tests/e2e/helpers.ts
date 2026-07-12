import { Page, expect } from '@playwright/test';

export async function loginAsAdmin(page: Page, baseURL: string) {
  // The seeded E2E admin (UsersSeeder) has 2FA enabled with a trusted device
  // whose client fingerprint is 'e2e-trusted-device'. Presetting it in
  // localStorage makes axios send it as X-Device-Fingerprint, so login
  // bypasses the 2FA setup/challenge flow entirely.
  await page.addInitScript(() => {
    localStorage.setItem('device_fingerprint', 'e2e-trusted-device');
  });

  await page.goto(baseURL + '/login');
  await page.getByLabel('Email').fill('e2e@admin.de');
  await page.getByLabel('Password').fill('adminadmin');
  await Promise.all([
    page.waitForURL(/\/$/),
    page.getByRole('button', { name: 'Login' }).click(),
  ]);

  await expect(page).toHaveURL(baseURL + '/');
}

export async function openInvoices(page: Page, baseURL: string) {
  await page.goto(baseURL + '/invoices');
  await expect(page.getByRole('heading', { name: 'Invoices' })).toBeVisible();
}

export async function openSettings(page: Page, baseURL: string) {
  await page.goto(baseURL + '/settings');
  await expect(page.getByRole('heading', { name: 'System Settings' })).toBeVisible();
}

/**
 * Set the global date format setting via the API instead of driving the
 * settings UI (the settings form is not what these tests are about, and
 * clicking through it is flaky). Requires a logged-in page with the app
 * loaded so window.axios (with CSRF + fingerprint interceptors) exists.
 * Callers must navigate afterwards so the store reloads the new setting.
 */
export async function setDateFormat(page: Page, format: 'DD/MM/YYYY' | 'MM/DD/YYYY' | 'YYYY-MM-DD') {
  const status = await page.evaluate(async (fmt) => {
    const res = await (window as any).axios.post('/api/settings/batch', { date_format: fmt });
    return res.status;
  }, format);
  expect(status).toBe(200);
}

/**
 * Open a kit ui-select identified by data-test (the attribute lands on the
 * component's root div) and choose the first option from its dropdown list.
 */
export async function selectFirstOption(page: Page, testId: string) {
  const root = page.locator(`[data-test="${testId}"]`);
  // ui-select opens from a button, ui-autocomplete from an input
  await root.locator('button, input').first().click();
  await root.locator('li').first().click();
}

/**
 * Open a kit ui-select or ui-autocomplete by its label text (for forms
 * without data-test attributes) and choose the first option. The kit renders
 * label + trigger (button for select, input for autocomplete) + option list
 * as direct children of one wrapper div.
 */
export async function selectFirstOptionByLabel(page: Page, label: string) {
  const root = page
    .locator(`[role="dialog"] div:has(> label:has-text("${label}"))`)
    .first();
  await root.locator('button, input').first().click();
  await root.locator('li').first().click();
}

// Format a date the same way the app's formatters.js does for the basic patterns.
export function formatDateString(date: Date, format: string): string {
  const day = date.getDate().toString().padStart(2, '0');
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const year = date.getFullYear();

  switch (format) {
    case 'MM/DD/YYYY':
      return `${month}/${day}/${year}`;
    case 'YYYY-MM-DD':
      return `${year}-${month}-${day}`;
    case 'DD/MM/YYYY':
    default:
      return `${day}/${month}/${year}`;
  }
}

// Native date inputs (which replaced v-date-picker) always hold YYYY-MM-DD.
export function todayISO(): string {
  return new Date().toISOString().slice(0, 10);
}
