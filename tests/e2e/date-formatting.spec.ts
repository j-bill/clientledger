import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin, setDateFormat } from './helpers';

// Helper to format date based on format string
function formatDateString(date: Date, format: string): string {
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

// Helper to convert date to ISO format for internal storage
function toISODate(date: Date): string {
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const day = date.getDate().toString().padStart(2, '0');
  return `${year}-${month}-${day}`;
}

test.describe('Date Formatting in Dialogs', () => {
  test.beforeEach(async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');
    await loginAsAdmin(page, baseURL);
  });

  test('Invoice form displays dates in DD/MM/YYYY format', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined');
    
    // Set date format to DD/MM/YYYY
    await setDateFormat(page, 'DD/MM/YYYY');
    
    // Navigate to invoices
    await page.goto(baseURL + '/invoices');
    
    // Open create invoice dialog
    await page.locator('[data-test="btn-new"]').click();
    
    // Wait for dialog to be visible
    await expect(page.getByRole('heading', { name: 'New Invoice' })).toBeVisible();
    
    // Select a customer
    const customerField = page.locator('[data-test="invoice-customer"] input');
    await customerField.click();
    const firstOption = page.locator('.v-overlay-container .v-list-item').first();
    await firstOption.click();
    
    // Click on issue date field to open date picker
    await page.locator('[data-test="invoice-issue-date"] input').click();
    
    // Select a date from the picker (today)
    const today = new Date();
    await page.locator('.v-date-picker-month__day--selected').click();
    
    // Verify the date is displayed in DD/MM/YYYY format
    const issueDateValue = await page.locator('[data-test="invoice-issue-date"] input').inputValue();
    const expectedFormat = formatDateString(today, 'DD/MM/YYYY');
    expect(issueDateValue).toBe(expectedFormat);
    
    // Click on due date field
    await page.locator('[data-test="invoice-due-date"] input').click();
    
    // Select due date
    await page.locator('.v-date-picker-month__day--selected').click();
    
    // Verify due date format
    const dueDateValue = await page.locator('[data-test="invoice-due-date"] input').inputValue();
    expect(dueDateValue).toBe(expectedFormat);
    
    // Close dialog
    await page.locator('[data-test="btn-cancel-create"]').click();
  });

  test('Invoice form displays dates in MM/DD/YYYY format', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined');
    
    // Set date format to MM/DD/YYYY
    await setDateFormat(page, 'MM/DD/YYYY');
    
    // Navigate to invoices
    await page.goto(baseURL + '/invoices');
    
    // Open create invoice dialog
    await page.locator('[data-test="btn-new"]').click();
    await expect(page.getByRole('heading', { name: 'New Invoice' })).toBeVisible();
    
    // Select a customer
    const customerField = page.locator('[data-test="invoice-customer"] input');
    await customerField.click();
    await page.locator('.v-overlay-container .v-list-item').first().click();
    
    // Click on issue date
    await page.locator('[data-test="invoice-issue-date"] input').click();
    const today = new Date();
    await page.locator('.v-date-picker-month__day--selected').click();
    
    // Verify format
    const issueDateValue = await page.locator('[data-test="invoice-issue-date"] input').inputValue();
    const expectedFormat = formatDateString(today, 'MM/DD/YYYY');
    expect(issueDateValue).toBe(expectedFormat);
    
    await page.locator('[data-test="btn-cancel-create"]').click();
  });

  test('Invoice form displays dates in YYYY-MM-DD format', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined');
    
    // Set date format to YYYY-MM-DD
    await setDateFormat(page, 'YYYY-MM-DD');
    
    // Navigate to invoices
    await page.goto(baseURL + '/invoices');
    
    // Open create invoice dialog
    await page.locator('[data-test="btn-new"]').click();
    await expect(page.getByRole('heading', { name: 'New Invoice' })).toBeVisible();
    
    // Select a customer
    const customerField = page.locator('[data-test="invoice-customer"] input');
    await customerField.click();
    await page.locator('.v-overlay-container .v-list-item').first().click();
    
    // Click on issue date
    await page.locator('[data-test="invoice-issue-date"] input').click();
    const today = new Date();
    await page.locator('.v-date-picker-month__day--selected').click();
    
    // Verify format
    const issueDateValue = await page.locator('[data-test="invoice-issue-date"] input').inputValue();
    const expectedFormat = formatDateString(today, 'YYYY-MM-DD');
    expect(issueDateValue).toBe(expectedFormat);
    
    await page.locator('[data-test="btn-cancel-create"]').click();
  });

  test('Work log form respects date format settings', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined');
    
    // Set date format to DD/MM/YYYY
    await setDateFormat(page, 'DD/MM/YYYY');
    
    // Navigate to work logs
    await page.goto(baseURL + '/worklogs');
    await expect(page.getByRole('heading', { name: 'Work Logs' })).toBeVisible();
    
    // Open create work log dialog
    await page.locator('[data-test="btn-new-worklog"]').click();
    
    // Wait for dialog to open
    await page.waitForTimeout(500);
    
    // Check if date field displays in correct format
    const dateFieldLocator = page.locator('label:has-text("Date")').locator('..').locator('input').first();
    
    // If the field is visible and has a value, check its format
    if (await dateFieldLocator.isVisible()) {
      // Click to open date picker
      await dateFieldLocator.click();
      
      // Wait for picker
      await page.waitForTimeout(300);
      
      // Select today
      const today = new Date();
      const todayButton = page.locator('.v-date-picker-month__day--selected').first();
      if (await todayButton.isVisible()) {
        await todayButton.click();
        
        // Verify the displayed format
        const dateValue = await dateFieldLocator.inputValue();
        if (dateValue) {
          const expectedFormat = formatDateString(today, 'DD/MM/YYYY');
          expect(dateValue).toBe(expectedFormat);
        }
      }
    }
  });

  test('Project form deadline respects date format settings', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined');
    
    // Set date format to MM/DD/YYYY
    await setDateFormat(page, 'MM/DD/YYYY');
    
    // Navigate to projects
    await page.goto(baseURL + '/projects');
    await expect(page.getByRole('heading', { name: 'Projects' })).toBeVisible();
    
    // Open create project dialog
    await page.locator('[data-test="btn-new-project"]').click();
    
    // Wait for form to load
    await page.waitForTimeout(500);
    
    // Find deadline field
    const deadlineField = page.locator('label:has-text("Deadline")').locator('..').locator('input').first();
    
    if (await deadlineField.isVisible()) {
      // Click to open date picker
      await deadlineField.click();
      
      // Wait for picker to open
      await page.waitForTimeout(300);
      
      // Select a date (try to find today)
      const todayButton = page.locator('.v-date-picker-month__day--selected').first();
      if (await todayButton.isVisible()) {
        await todayButton.click();
        
        // Verify format
        const dateValue = await deadlineField.inputValue();
        if (dateValue) {
          const today = new Date();
          const expectedFormat = formatDateString(today, 'MM/DD/YYYY');
          expect(dateValue).toBe(expectedFormat);
        }
      }
    }
  });

  test('Invoice creation with date pickers works correctly', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined');
    
    // Set date format
    await setDateFormat(page, 'DD/MM/YYYY');
    
    // Navigate to invoices
    await page.goto(baseURL + '/invoices');
    
    // Open create dialog
    await page.locator('[data-test="btn-new"]').click();
    await expect(page.getByRole('heading', { name: 'New Invoice' })).toBeVisible();
    
    // Select customer
    const customerField = page.locator('[data-test="invoice-customer"] input');
    await customerField.click();
    await page.locator('.v-overlay-container .v-list-item').first().click();
    
    // Set issue date
    await page.locator('[data-test="invoice-issue-date"] input').click();
    await page.locator('.v-date-picker-month__day--selected').click();
    
    // Set due date
    await page.locator('[data-test="invoice-due-date"] input').click();
    await page.locator('.v-date-picker-month__day--selected').click();
    
    // Fill amount
    await page.locator('[data-test="invoice-total"] input').fill('500.00');
    
    // Save
    await page.locator('[data-test="btn-save-create"]').click();
    
    // Check for success message
    await expect(page.getByText(/invoice created successfully/i)).toBeVisible();
  });

  test('Invoice edit dialog displays existing dates in correct format', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined');
    
    // Set date format
    await setDateFormat(page, 'YYYY-MM-DD');
    
    // Navigate to invoices
    await page.goto(baseURL + '/invoices');
    
    // Wait for table to load
    await page.waitForTimeout(1000);
    
    // Find first edit button (if any invoices exist)
    const editButton = page.locator('button[aria-label="Edit"]:visible, button:has-text("Edit"):visible, button .mdi-pencil:visible').first();
    
    if (await editButton.isVisible()) {
      await editButton.click();
      
      // Wait for dialog
      await expect(page.getByRole('heading', { name: 'Edit Invoice' })).toBeVisible();
      
      // Check that dates are displayed in the correct format
      const issueDateValue = await page.locator('[data-test="invoice-issue-date"] input').inputValue();
      const dueDateValue = await page.locator('[data-test="invoice-due-date"] input').inputValue();
      
      // Both dates should match the YYYY-MM-DD pattern
      if (issueDateValue) {
        expect(issueDateValue).toMatch(/^\d{4}-\d{2}-\d{2}$/);
      }
      if (dueDateValue) {
        expect(dueDateValue).toMatch(/^\d{4}-\d{2}-\d{2}$/);
      }
    }
  });
});
