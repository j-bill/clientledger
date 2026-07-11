import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  fullyParallel: true,
  // All tests share one backend + database; parallel workers race on global
  // state (settings, sequential invoice numbers), so run a single worker.
  workers: 1,
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: 'https://clientledger.test',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retry-with-video',
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
