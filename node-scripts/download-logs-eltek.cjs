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
console.log("⏳ Waiting for and clicking the Logs menu...");

try {
  const logsMenu = page.locator('#log_menu'); // not `> a` for now
  await logsMenu.waitFor({ state: 'attached', timeout: 60000 });
  await logsMenu.scrollIntoViewIfNeeded();
  await logsMenu.click(); // click the whole menu item if it works
  console.log("✅ Clicked Logs menu.");
} catch (err) {
  console.error("❌ Failed to click Logs menu.");
  console.error(err);
}


console.log("⏳ Waiting for 'Save logs to file' element...");
console.log("⏳ Waiting for 'Save logs to file' link (by id)...");

console.log("⏳ Expanding Logs menu...");
await page.locator('#log_menu > a').click();

console.log("⏳ Waiting for Save logs to file link...");
const saveLogLink = page.locator('#button_log_save');

try {
  await saveLogLink.waitFor({ state: 'visible', timeout: 10000 });
  console.log("✅ Found and visible: Save logs to file");
  await saveLogLink.click();
} catch (err) {
  console.error("❌ Couldn't find #button_log_save within timeout");
  try {
    await page.screenshot({ path: './screenshots/log_save_not_found.png' });
    console.log("📸 Screenshot saved: ./screenshots/log_save_not_found.png");
  } catch (e) {
    console.error("⚠️ Screenshot failed to save:", e.message);
  }
}

console.log("✅ 'Save logs to file' is visible!");

// Now wait for and click Save logs
await page.locator('#button_log_save').waitFor({ state: 'visible', timeout: 10000 });
await page.click('#button_log_save');

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
