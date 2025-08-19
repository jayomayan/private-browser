const { chromium } = require('playwright');
const fs = require('fs');
const os = require('os');
const path = require('path');

// Get CLI arguments
const args = process.argv.slice(2);
const IP = args[0] || '10.194.64.131';
const USERNAME = args[1] || 'admin';
const PASSWORD = args[2] || 'admin@123';

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

        await page.getByRole('textbox', { name: 'User Name:' }).fill(USERNAME);
        await page.getByRole('textbox', { name: 'User Name:' }).press('Tab');
        await page.getByRole('textbox', { name: 'Password:' }).fill(PASSWORD);
        await page.getByRole('button', { name: 'Login' }).click();
        console.error("✅ Logged in successfully.");

        await page.locator('#companyLogo').waitFor({ state: 'visible', timeout: 100000 });

        console.log("✅ Company Logo Shown.");
        await page.waitForTimeout(30000);

        await page.getByRole('cell', { name: 'Logs' }).getByRole('img').click();
        console.log("✅ Clicked Logs.");

        const fromInput = page.locator('#logDatetimeFrom');
        await fromInput.waitFor({ state: 'visible', timeout: 60000 });
        await fromInput.fill('2025-08-06');
        console.log("✅ Filled 'From' Date.");

        const toInput = page.locator('#logDatetimeTo');
        await toInput.waitFor({ state: 'visible', timeout: 60000 });
        await toInput.fill('2025-08-07');
        console.log("✅ Filled 'To' Date.");

        //await page.locator('#logDatetimeFrom').fill('2025-08-06');
        //await page.locator('#logDatetimeTo').fill('2025-08-07');

        const downloadPromise = page.waitForEvent('download', { timeout: 120000 });
        await page.getByRole('button', { name: 'Download' }).click();
        console.log("✅ Download button clicked.");

        await page.locator('table#loadingBar411', { hasText: 'File Downloaded.' })
          .waitFor({ state: 'visible', timeout: 120000 });

        console.log('✅ UI shows file downloaded.');

        const download = await downloadPromise;
        const tempPath = await download.path();
        const content = fs.readFileSync(tempPath, 'utf8');
        console.log("✅ Logs Downloaded:", content);

        await page.getByRole('cell', { name: 'Event Logs' }).click();
        await page.locator('#eventLogDatetimeFrom').fill('2025-08-06');
        await page.locator('#eventLogDatetimeTo').fill('2025-08-07');
        await page.getByRole('button', { name: 'Download' }).click();

        console.error("✅ Event Logs Download triggered.");


        await page.getByRole('cell', { name: 'Event Logs' }).click();
        await page.locator('#eventLogDatetimeFrom').fill('2025-08-18');
        await page.locator('#eventLogDatetimeTo').fill('2025-08-19');
        const download1Promise = page.waitForEvent('download', { timeout: 120000 });
        await page.getByRole('button', { name: 'Download' }).click();
        console.log("✅ Download button clicked.");

        await page.locator('table#loadingBar421', { hasText: 'File Downloaded.' })
          .waitFor({ state: 'visible', timeout: 120000 });

        const download1 = await download1Promise;
        const tempPath1 = await download.path();
        const content1 = fs.readFileSync(tempPath1, 'utf8');
        console.log("✅ Logs Downloaded:", content1);

    } catch (err) {
        console.error("❌ Script Error:", err);
    } finally {
        await context.close();
        fs.rmSync(userDataDir, { recursive: true, force: true });
    }
})();
