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
 * Open a Vuetify select/autocomplete by clicking its field wrapper
 * (clicking the inner <input> is intercepted by .v-field__input)
 * and choose the first option from the overlay list.
 */
export async function selectFirstOption(page: Page, testId: string) {
  await page.locator(`[data-test="${testId}"] .v-field`).click();
  const option = page.locator('.v-overlay-container .v-list-item').first();
  await option.click();
}

/**
 * Open a Vuetify select/autocomplete identified by its label text (for forms
 * without data-test attributes) and choose the first option. Closes the menu
 * afterwards so it also works for multi-selects that stay open.
 */
export async function selectFirstOptionByLabel(page: Page, label: string) {
  const field = page
    .locator('.v-dialog .v-input', { has: page.locator('label', { hasText: label }) })
    .first()
    .locator('.v-field');
  await field.click();
  await page.locator('.v-overlay-container .v-list-item').first().click();
  await page.keyboard.press('Escape');
}

/**
 * In the currently open v-date-picker, click today's date and close the menu.
 * Falls back to the already-selected day when the field was prefilled.
 */
export async function pickTodayInOpenDatePicker(page: Page) {
  const picker = page.locator('.v-overlay-container .v-date-picker').last();
  await expect(picker).toBeVisible();

  const selected = picker.locator('.v-date-picker-month__day--selected button');
  if (await selected.count()) {
    await selected.first().click();
  } else {
    const day = String(new Date().getDate());
    await picker
      .locator('.v-date-picker-month__day:not(.v-date-picker-month__day--adjacent) button')
      .filter({ hasText: new RegExp(`^${day}$`) })
      .first()
      .click();
  }

  // v-menu has close-on-content-click=false, so close it explicitly.
  await page.keyboard.press('Escape');
  await expect(picker).toBeHidden();
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
