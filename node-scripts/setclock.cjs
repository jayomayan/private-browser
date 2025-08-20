const { chromium } = require('playwright');
const fs = require('fs');
const os = require('os');
const path = require('path');

// Get CLI arguments
const args = process.argv.slice(2);
const IP = args[0] || '10.194.67.249';
const BRAND = args[1] || 'Enetek';

const LOGIN_URL = `http://${IP}/`;

(async () => {
    const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'chrome-profile-'));

    const context = await chromium.launchPersistentContext(userDataDir, {
        headless: true,
        acceptDownloads: true,
        viewport: { width: 1920, height: 1080 },
        userAgent: "Mozilla/5.0",
        args: [
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-crash-reporter',
            '--no-first-run',
            '--no-default-browser-check'
        ]
    });

    const page = await context.newPage();

        try {
        console.error("✅ Opening login page...");
        await page.goto(LOGIN_URL, { timeout: 30000 });
        await page.locator('iframe[name="I1"]').contentFrame().getByRole('textbox', { name: 'User name' }).fill('admin');
        await page.locator('iframe[name="I1"]').contentFrame().getByRole('textbox', { name: 'User name' }).press('Tab');
        await page.locator('iframe[name="I1"]').contentFrame().getByRole('textbox', { name: 'Password' }).fill('admin');
        await page.locator('iframe[name="I1"]').contentFrame().getByRole('button', { name: 'Login' }).click();
        await page.locator('a:has-text("System Config")').waitFor({ state: 'visible', timeout: 60000 });
        await page.locator('iframe[name="I1"]').contentFrame().getByRole('link', { name: 'System Config' }).click();
        await page.locator('iframe[name="I1"]').contentFrame().getByRole('link', { name: 'Network Config' }).click();
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip1"]').click();
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip1"]').fill('10');
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip1"]').press('Tab');
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip2"]').fill('10');
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip2"]').press('Tab');
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip3"]').fill('0');
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip3"]').press('Tab');
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntpserverip4"]').fill('63');
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntptimezone"]').click();
        await page.locator('iframe[name="I1"]').contentFrame().locator('iframe[name="iframe"]').contentFrame().locator('input[name="ntptimezone"]').fill('8');
        console.error("✅ NTP settings updated successfully.");

    } catch (err) {
        console.error("❌ Script Error:", err);
    } finally {
        await context.close();
        fs.rmSync(userDataDir, { recursive: true, force: true });
    }
})();
