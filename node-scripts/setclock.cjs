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
        await page.goto(LOGIN_URL, { timeout: 60000, waitUntil: 'domcontentloaded' });

        // ----- outer iframe -----
        const f1 = page.frameLocator('iframe[name="I1"]');

        // login
        await f1.getByRole('textbox', { name: /user\s*name/i }).fill('admin');
        await f1.getByRole('textbox', { name: /password/i }).fill('admin');
        await f1.getByRole('button',  { name: /login/i }).click();

        // wait for post-login UI inside the same frame
        await f1.getByRole('link', { name: /system\s*config/i }).waitFor({ state: 'visible', timeout: 60000 });

        // navigate
        await f1.getByRole('link', { name: /system\s*config/i }).click();
        await f1.getByRole('link', { name: /network\s*config/i }).click();

        // ----- inner iframe inside I1 -----
        const f2 = f1.frameLocator('iframe[name="iframe"]');

        // fill NTP fields (wait for first input to be ready)
        await f2.locator('input[name="ntpserverip1"]').waitFor({ state: 'visible', timeout: 30000 });

        await f2.locator('input[name="ntpserverip1"]').fill('10');
        await f2.locator('input[name="ntpserverip2"]').fill('10');
        await f2.locator('input[name="ntpserverip3"]').fill('0');
        await f2.locator('input[name="ntpserverip4"]').fill('63');

        await f2.locator('input[name="ntptimezone"]').click();
        await f2.locator('input[name="ntptimezone"]').fill('8');

        console.error("✅ NTP settings updated successfully.");

    } catch (err) {
        console.error("❌ Script Error:", err);
    } finally {
        await context.close();
        fs.rmSync(userDataDir, { recursive: true, force: true });
    }
})();
