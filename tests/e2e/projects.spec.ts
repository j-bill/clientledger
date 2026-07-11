import { test, expect } from '@playwright/test';
import { loginAsAdmin, selectFirstOptionByLabel } from './helpers';

test.describe('Projects e2e', () => {
  test.beforeEach(async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');
    await loginAsAdmin(page, baseURL);
    await page.goto(baseURL + '/projects');
    await expect(page.getByRole('heading', { name: 'Projects' })).toBeVisible();
  });

  test('project table lists seeded projects', async ({ page }) => {
    await expect(page.locator('table tbody tr').first()).toBeVisible();
  });

  test('create a project via dialog and see it in the table', async ({ page }) => {
    await page.locator('[data-test="btn-new-project"]').click();

    const dialog = page.locator('.v-dialog');
    await expect(dialog).toBeVisible();

    const uniqueName = `E2E Project ${Date.now()}`;
    await dialog.getByLabel('Project Name').fill(uniqueName);
    await selectFirstOptionByLabel(page, 'Customer');
    await selectFirstOptionByLabel(page, 'Assigned Users');

    await dialog.getByRole('button', { name: 'Save' }).click();
    await expect(dialog).toBeHidden();

    await page.getByRole('textbox', { name: 'Search' }).fill(uniqueName);
    await expect(page.locator('tr', { hasText: uniqueName })).toBeVisible();
  });
});
