import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers';

test.describe('Work logs e2e', () => {
  test.beforeEach(async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');
    await loginAsAdmin(page, baseURL);
    await page.goto(baseURL + '/work-logs');
    await expect(page.getByRole('heading', { name: 'Work Logs' })).toBeVisible();
  });

  test('work log table lists seeded entries', async ({ page }) => {
    await expect(page.locator('table tbody tr').first()).toBeVisible();
  });

  test('new work log dialog opens with expected fields and can be cancelled', async ({ page }) => {
    await page.locator('[data-test="btn-new-worklog"]').click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();

    for (const label of ['Customer', 'Project', 'Date', 'Description']) {
      await expect(dialog.locator('label', { hasText: label }).first()).toBeAttached();
    }

    await dialog.getByRole('button', { name: 'Cancel' }).click();
    await expect(dialog).toBeHidden();
  });
});
