// scripts/export-logs.js
process.env.PLAYWRIGHT_BROWSERS_PATH = "/var/www/toolbox/.playwright-browsers";

const { chromium } = require("playwright");
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

  await page.goto(LOGIN_URL, { timeout: 45_000, waitUntil: "domcontentloaded" });

await page.getByRole('button', { name: 'Login' }).click();
await page.getByRole('textbox', { name: 'User name' }).fill('admin');
await page.getByRole('textbox', { name: 'User name' }).press('Tab');
await page.getByRole('textbox', { name: 'Password' }).fill('admin');
await page.getByRole('textbox', { name: 'Password' }).press('Enter');

// Wait until #log is visible
await page.locator('#log').waitFor({ state: 'visible', timeout: 100000 });
await page.locator('#log').click();

// Wait until "Save logs to file" link is visible
await page.getByRole('link', { name: 'Save logs to file' })
          .waitFor({ state: 'visible', timeout: 100000 });
await page.getByRole('link', { name: 'Save logs to file' }).click();

// Wait until legend appears
await page.locator('legend').waitFor({ state: 'visible', timeout: 100000 });

await page.locator('#eventlog').check();
await page.locator('#numofeventlogitems').click();
await page.locator('#numofeventlogitems').fill('1000');
await page.getByRole('button', { name: 'Generate log(s)' }).click();

// Wait until "Status: Complete!" text appears
await page.getByText('Status: Complete!')
          .waitFor({ state: 'visible', timeout: 100000 });

    // Download log
    console.error("➡️ Downloading log file...");
    const [download] = await Promise.all([
      page.waitForEvent("download"),
      page.getByRole('button', { name: 'Download log' }).click()
    ]);

    const suggested = download.suggestedFilename();
    const outPath = path.join(OUTPUT_DIR, suggested);

    // Ensure output dir exists
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });

    // Save to disk
    await download.saveAs(outPath);
    console.error(`✅ Saved: ${outPath}`);

    // Also print path to STDOUT (so Laravel can capture it)
    console.log(outPath);
  } catch (err) {
    console.error("❌ Script Error:", err?.message || err);
    process.exitCode = 1;
  } finally {
    await context.close();
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }
})();
