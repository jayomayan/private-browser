// scripts/export-logs.js
process.env.PLAYWRIGHT_BROWSERS_PATH = "/var/www/toolbox/.playwright-browsers";

const { chromium, expect } = require("playwright");
const fs = require("fs");
const os = require("os");
const path = require("path");

// ---- CLI ARGS ----
// Usage: node scripts/export-logs.js 10.194.82.152 admin admin ./storage/app/exports
const args = process.argv.slice(2);
const IP = args[0] || "10.194.82.152";
const USERNAME = args[1] || "admin";
const PASSWORD = args[2] || "admin";
const OUTPUT_DIR = args[3] || process.cwd();

const LOGIN_URL = `http://${IP}/INDEX.HTM`;

(async () => {
  // Temp user profile for more stable sessions
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), "chrome-profile-"));

  const context = await chromium.launchPersistentContext(userDataDir, {
    headless: true,
    acceptDownloads: true,
    viewport: { width: 1920, height: 1080 },
    userAgent: "Mozilla/5.0",
    args: [
      "--no-sandbox",
      "--disable-dev-shm-usage",
      "--disable-crash-reporter",
      "--no-first-run",
      "--no-default-browser-check",
    ],
  });

  const page = await context.newPage();

  try {
    console.log('🌐 Navigating to login page...');
    await page.goto(LOGIN_URL, { timeout: 45_000, waitUntil: "domcontentloaded" });

    console.log('🔐 Clicking Login button...');
    await page.getByRole('button', { name: 'Login' }).click();

    console.log('👤 Filling in username...');
    await page.getByRole('textbox', { name: 'User name' }).fill(USERNAME);
    await page.getByRole('textbox', { name: 'User name' }).press('Tab');

    console.log('🔑 Filling in password...');
    await page.getByRole('textbox', { name: 'Password' }).fill(PASSWORD);
    await page.getByRole('textbox', { name: 'Password' }).press('Enter');

    console.log('📁 Waiting for Logs menu to appear...');
    await expect(page.locator('#log')).toBeVisible();

    console.log('📂 Clicking Logs menu...');
    await page.locator('#log').click();

    console.log('📂 Clicking Logs menu again (to fully expand if needed)...');
    await page.locator('#log').click();

    console.log("🔍 Waiting for 'Save logs to file' link by ID...");
    const saveLogsLink = page.locator('#button_log_save');
    await expect(saveLogsLink).toBeVisible();

    console.log('💾 Clicking Save logs to file...');
    await saveLogsLink.click();

    console.log('✅ Save logs screen loaded. Configuring options...');

    console.log('📌 Checking "Event log"...');
    await page.locator('#eventlog').check();

    console.log('🔢 Filling in number of log items...');
    await page.locator('#numofeventlogitems').click();
    await page.locator('#numofeventlogitems').fill('1000');

    console.log('⚙️ Generating logs...');
    await page.getByRole('button', { name: 'Generate log(s)' }).click();

    console.log('⏳ Waiting for generation to complete...');
    await expect(page.locator('#progress')).toContainText('Status: Complete!');

    console.log('📥 Preparing to download log file...');
    const downloadPromise = page.waitForEvent('download');

    console.log('⬇️ Clicking Download log button...');
    await page.getByRole('button', { name: 'Download log' }).click();

    const download = await downloadPromise;
    const filename = path.join(OUTPUT_DIR, `logs-${Date.now()}.zip`);
    await download.saveAs(filename);
    console.log(`✅ Downloaded log file saved as: ${filename}`);

    await context.close();
  } catch (err) {
    console.error("❌ Script failed:", err);
    await page.screenshot({ path: path.join(OUTPUT_DIR, 'error.png') });
    console.log("📸 Screenshot of error state saved.");
    process.exit(1);
  }
})();
