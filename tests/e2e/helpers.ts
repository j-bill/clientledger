import { Page, expect } from '@playwright/test';

export async function loginAsAdmin(page: Page, baseURL: string) {
  // Go to login page
  await page.goto(baseURL + '/login');
  // Fill email and password (defaults are prefilled, but be explicit for stability)
  await page.getByLabel('Email').fill('admin@admin.de');
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
