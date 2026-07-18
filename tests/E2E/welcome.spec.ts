import { expect, test } from '@playwright/test';

test('mostra a apresentação multi-tenant da Sprint 1', async ({ page }) => {
    await page.goto('http://app.estamparia.test:8000/');
    await expect(page.getByRole('heading', { name: /cada estamparia/i })).toBeVisible();
    await expect(page.getByText(/sprint 1/i).first()).toBeVisible();
});
