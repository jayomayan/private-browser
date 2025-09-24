process.env.PLAYWRIGHT_BROWSERS_PATH = "/var/www/toolbox/.playwright-browsers";
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
        acceptDownloads: false,
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
        //console.error("✅ Opening login page...");
        await page.goto(LOGIN_URL, { timeout: 30000 });
        await page.waitForTimeout(2000);

        const frame = await page.frame({ name: "I1" });
        if (!frame) throw new Error("❌ Could not find iframe 'I1'.");

        //console.error("✅ Logging in...");
        await frame.fill('input[name="T1"]', USERNAME);
        await frame.fill('input[name="T2"]', PASSWORD);
        await frame.click('button:has-text("Login")');
        await page.waitForTimeout(2000);
        //console.error("✅ Logged in successfully.");

        // Click About
        await frame.getByRole('link', { name: 'About' }).click();
        await page.waitForTimeout(2000);

        // Find nested iframe inside frame "I1"
        const nested = frame.childFrames().find(f => f.name() === 'iframe');
        if (!nested) throw new Error("❌ Could not find nested iframe inside 'I1'.");

        //console.error("✅ Extracting version values...");

        // Extract values (use .inputValue(), fallback to .getAttribute("value") if read-only)
        const versions = {
            arm_version: await nested.locator('input[name="armversion"]').inputValue().catch(() => nested.locator('input[name="armversion"]').getAttribute('value')),
            stm32_version: await nested.locator('input[name="stm32version"]').inputValue().catch(() => nested.locator('input[name="stm32version"]').getAttribute('value')),
            web_version: await nested.locator('input[name="webversion"]').inputValue().catch(() => nested.locator('input[name="webversion"]').getAttribute('value')),
            kernel_version: await nested.locator('input[name="kernelversion"]').inputValue().catch(() => nested.locator('input[name="kernelversion"]').getAttribute('value')),
            mib_version: await nested.locator('input[name="mibversion"]').inputValue().catch(() => nested.locator('input[name="mibversion"]').getAttribute('value')),
        };

        // Print results as JSON
        console.log(JSON.stringify(versions, null, 2));

    } catch (err) {
        console.error("❌ Script Error:", err);
    } finally {
        await context.close();
        fs.rmSync(userDataDir, { recursive: true, force: true });
    }
})();
