// One-off screenshot tour of key pages, run against the local dev server.
// Bypasses 2FA the same way tests/e2e/helpers.ts::loginAsAdmin does: presets
// localStorage.device_fingerprint to the seeded trusted device so login
// completes without the 2FA setup/challenge flow.
//
// Usage: node tests/e2e/screenshot-tour.mjs [output-dir]
//
// Run `php artisan migrate:fresh --seed` first for a clean, freshly seeded
// dataset. Screenshots are capped to the viewport (no full-page scroll) so
// long lists don't stretch the image past the configured size.

import { chromium } from 'playwright';

const baseURL = 'https://clientledger.test';
const outDir = process.argv[2] || 'screenshots';

const browser = await chromium.launch();
const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1920, height: 1080 },
});
const page = await context.newPage();

await page.addInitScript(() => {
    localStorage.setItem('device_fingerprint', 'e2e-trusted-device');
});

await page.goto(baseURL + '/login');
await page.waitForTimeout(500);
await page.screenshot({ path: `${outDir}/01-login.png` });

await page.getByLabel('Email').fill('e2e@admin.de');
await page.getByLabel('Password').fill('adminadmin');
await page.getByRole('button', { name: 'Login' }).click();
await page.waitForURL(/\/$/, { timeout: 15000 });
await page.waitForTimeout(1000);

// Switch the UI language to German for this tour.
await page.evaluate(async () => {
    await window.axios.post('/api/settings/batch', { language: 'de' });
});
await page.reload();
await page.waitForTimeout(1500);
await page.screenshot({ path: `${outDir}/02-home.png` });

const pages = [
    ['projects', '03-projects'],
    ['customers', '04-customers'],
    ['invoices', '05-invoices'],
    ['expenses', '06-expenses'],
    ['work-logs', '07-worklogs'],
    ['users', '08-users'],
    ['profile', '09-profile'],
    ['settings', '10-settings'],
];

for (const [route, name] of pages) {
    await page.goto(`${baseURL}/${route}`);
    await page.waitForTimeout(1200);
    await page.screenshot({ path: `${outDir}/${name}.png` });
}

await browser.close();
console.log(`Done. Screenshots in ${outDir}/`);
