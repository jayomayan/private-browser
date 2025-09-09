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
console.error("✅ Opened login page.");

await page.getByRole('button', { name: 'Login' }).click();
console.error("✅ Clicked Login button.");

await page.getByRole('textbox', { name: 'User name' }).fill('admin');
console.error("✅ Filled username.");

await page.getByRole('textbox', { name: 'User name' }).press('Tab');
await page.getByRole('textbox', { name: 'Password' }).fill('admin');
console.error("✅ Filled password.");

// Press Enter
await page.getByRole('textbox', { name: 'Password' }).press('Enter');
console.error("✅ Submitted login form.");

// Wait until #log is visible
console.error("⏳ Waiting for #log menu...");
await page.locator('#log').waitFor({ state: 'visible', timeout: 100000 });
console.error("✅ #log menu visible.");
await page.locator('#log').click();
console.error("✅ Clicked #log menu.");

console.error("⏳ Waiting for #button_log_save...");
const saveBtn = page.locator('#button_log_save');

await saveBtn.waitFor({ state: 'visible', timeout: 100000 });

// Optional: verify it really has the right text
const btnText = await saveBtn.textContent();
if (!/save\s*logs\s*to\s*file/i.test(btnText || "")) {
  throw new Error(`❌ Unexpected button text: "${btnText}"`);
}

console.error("✅ Found 'Save logs to file' button.");
await saveBtn.click();
console.error("✅ Clicked 'Save logs to file'.");

// Wait until legend appears
console.error("⏳ Waiting for legend...");
await page.locator('legend').waitFor({ state: 'visible', timeout: 100000 });
console.error("✅ Legend visible.");

// Select event log and set items
await page.locator('#eventlog').check();
console.error("✅ Checked 'eventlog'.");

await page.locator('#numofeventlogitems').click();
await page.locator('#numofeventlogitems').fill('1000');
console.error("✅ Set number of event log items = 1000.");

// Generate logs
await page.getByRole('button', { name: 'Generate log(s)' }).click();
console.error("✅ Clicked 'Generate log(s)'.");

// Wait for status complete
console.error("⏳ Waiting for 'Status: Complete!'...");
await page.getByText('Status: Complete!')
          .waitFor({ state: 'visible', timeout: 100000 });
console.error("✅ Status: Complete!");

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
