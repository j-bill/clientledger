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
  
  // Handle potential 2FA setup/challenge
  // Wait a bit to see if we're redirected to 2FA
  await page.waitForTimeout(1000);
  
  const currentUrl = page.url();
  
  // Check if we're on 2FA setup page
  if (currentUrl.includes('/two-factor-setup')) {
    // Click skip button if available
    const skipButton = page.getByRole('button', { name: /skip/i });
    if (await skipButton.isVisible()) {
      await skipButton.click();
      await page.waitForURL(baseURL + '/');
    }
  }
  
  // Check if we're on 2FA challenge page
  if (currentUrl.includes('/two-factor-challenge')) {
    // Enter default 2FA code
    await page.getByLabel(/code|authentication/i).fill('000000');
    await page.getByRole('button', { name: /verify|submit/i }).click();
    await page.waitForURL(baseURL + '/');
  }
  
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

export async function setDateFormat(page: Page, format: 'DD/MM/YYYY' | 'MM/DD/YYYY' | 'YYYY-MM-DD') {
  // Navigate to settings
  await page.goto('/settings');
  
  // Click on Date & Time tab
  await page.getByRole('tab', { name: /date.*time/i }).click();
  
  // Select the date format
  await page.locator('label:has-text("Date Format")').locator('..').locator('input').click();
  await page.getByRole('option', { name: format }).click();
  
  // Save settings
  await page.getByRole('button', { name: /save.*settings/i }).click();
  
  // Wait for success message
  await expect(page.getByText(/settings saved successfully/i)).toBeVisible();
}
