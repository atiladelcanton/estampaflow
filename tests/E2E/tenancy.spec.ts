import { test, expect } from '@playwright/test';

test('owner enters the seeded tenant workspace', async ({ page }) => {
    await page.goto('http://app.estamparia.test:8000/login');
    await page.getByLabel('E-mail').fill('admin@delka.local');
    await page.getByLabel('Senha').fill('password');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await expect(page.getByRole('heading', { name: 'Seus ambientes' })).toBeVisible();
    await page.getByText('Estamparia Alpha').click();

    await expect(page).toHaveURL(/alpha\.estamparia\.test:8000\/dashboard/);
    await expect(page.getByRole('heading', { name: 'Estamparia Alpha' })).toBeVisible();
});
