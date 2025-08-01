const { chromium } = require('playwright');
const fs = require('fs');
const os = require('os');
const path = require('path');

// Get CLI arguments
const args = process.argv.slice(2);
const IP = args[0] || '10.194.67.249';
const USERNAME = args[1] || 'admin';
const PASSWORD = args[2] || 'admin';

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
        await page.waitForTimeout(3000);

        const frame = await page.frame({ name: "I1" });
        if (!frame) throw new Error("❌ Could not find iframe 'I1'.");

        await frame.fill('input[name="T1"]', USERNAME);
        await frame.fill('input[name="T2"]', PASSWORD);
        await frame.click('button:has-text("Login")');
        console.error("✅ Logged in successfully.");

        await page.goto(`http://${IP}/cgi-bin/historyfault_info`, { timeout: 30000 });
        console.error("✅ Navigated to logs page.");

        await page.click(".ts_dropbtn");
        console.error("✅ Clicked 'Export Data' button.");

        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.click('a[data-type="csv"]')
        ]);

        const tempPath = await download.path();
        const content = fs.readFileSync(tempPath, 'utf8');
        console.log(content);

    } catch (err) {
        console.error("❌ Script Error:", err);
    } finally {
        await context.close();
        fs.rmSync(userDataDir, { recursive: true, force: true });
    }
})();
