import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers';

test.describe('Customers e2e', () => {
  test('create a customer via dialog and see it in the table', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');

    await loginAsAdmin(page, baseURL);
    await page.goto(baseURL + '/customers');
    await expect(page.getByRole('heading', { name: 'Customers' })).toBeVisible();

    await page.getByRole('button', { name: 'New Customer' }).click();

    const dialog = page.locator('.v-dialog');
    await expect(dialog).toBeVisible();

    const uniqueName = `E2E Customer ${Date.now()}`;
    await dialog.getByLabel('Name', { exact: true }).fill(uniqueName);
    await dialog.getByLabel('Contact Email').fill('e2e-customer@example.com');
    await dialog.getByLabel('City').fill('Berlin');

    await dialog.getByRole('button', { name: 'Save' }).click();
    await expect(dialog).toBeHidden();

    // Find the new customer via search so pagination can't hide it
    await page.getByRole('textbox', { name: 'Search' }).fill(uniqueName);
    await expect(page.locator('tr', { hasText: uniqueName })).toBeVisible();
  });

  test('customer table lists seeded customers', async ({ page, baseURL }) => {
    await loginAsAdmin(page, baseURL!);
    await page.goto(baseURL + '/customers');
    await expect(page.getByRole('heading', { name: 'Customers' })).toBeVisible();
    await expect(page.locator('table tbody tr').first()).toBeVisible();
  });
});
