const { chromium } = require('playwright');
const fs = require('fs');
const os = require('os');
const path = require('path');

// Get CLI arguments
const args = process.argv.slice(2);
const IP = args[0] || '10.194.72.40';
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

        // login page *does* use an iframe named I1
        const login = page.frameLocator('iframe[name="I1"]');
        await login.getByRole('textbox', { name: /user\s*name/i }).fill('admin');
        await login.getByRole('textbox', { name: /password/i }).fill('admin');
        await login.getByRole('button',  { name: /login/i }).click();
        console.error("✅ Logged in successfully.");

        // go to Network Config (this page is NOT inside I1)
        await page.goto(`http://${IP}/cgi-bin/network_set`, { timeout: 60000, waitUntil: 'domcontentloaded' });

        // wait for the form to be visible
        const form = page.locator('form.layui-form[action="network_set"]');
        await form.waitFor({ state: 'visible', timeout: 60000 });
        console.error("✅ Network Configuration form is visible.");

        // fill NTP server IPs
        await form.locator('input[name="ntpserverip1"]').fill('10');
        await form.locator('input[name="ntpserverip2"]').fill('10');
        await form.locator('input[name="ntpserverip3"]').fill('0');
        await form.locator('input[name="ntpserverip4"]').fill('63');

        // timezone
        await form.locator('input[name="ntptimezone"]').fill('8');

        console.error("✅ NTP fields set. Submitting...");

        // submit (note: target="iframe" means response loads into an iframe named "iframe")
        await form.locator('input[type="submit"][name="submit"]').click();

        // optional: wait for the result iframe to load something
        const resultFrame = page.frameLocator('iframe[name="iframe"]');
        await resultFrame.locator('body').waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
        // If the firmware shows a message, you can look for it here, e.g.:
        // await resultFrame.getByText(/(success|saved|applied|ok)/i).first().waitFor({ timeout: 5000 }).catch(()=>{});

        console.error("✅ NTP settings submitted.");
    } catch (err) {
        console.error("❌ Script Error:", err);
    } finally {
        await context.close();
        fs.rmSync(userDataDir, { recursive: true, force: true });
    }
})();
