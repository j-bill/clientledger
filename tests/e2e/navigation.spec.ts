import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers';

test.describe('Navigation smoke', () => {
  test.beforeEach(async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');
    await loginAsAdmin(page, baseURL);
  });

  const pages: Array<{ path: string; heading: string }> = [
    { path: '/', heading: 'Your Money Today' },
    { path: '/projects', heading: 'Projects' },
    { path: '/customers', heading: 'Customers' },
    { path: '/invoices', heading: 'Invoices' },
    { path: '/expenses', heading: 'Expenses' },
    { path: '/work-logs', heading: 'Work Logs' },
    { path: '/settings', heading: 'System Settings' },
  ];

  for (const { path, heading } of pages) {
    test(`page ${path} loads with heading "${heading}"`, async ({ page, baseURL }) => {
      await page.goto(baseURL + path);
      await expect(page.getByRole('heading', { name: heading })).toBeVisible();
    });
  }

  test('unknown route shows not found page', async ({ page, baseURL }) => {
    await page.goto(baseURL + '/definitely-not-a-real-page');
    await expect(page.getByText('Page Not Found')).toBeVisible();
  });

  test('unauthenticated user is redirected to login', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.goto(baseURL + '/invoices');
    await expect(page).toHaveURL(/\/login/);
    await context.close();
  });
});
