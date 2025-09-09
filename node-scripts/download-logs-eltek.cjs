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
    console.error(`➡️ Opening ${LOGIN_URL} ...`);
    await page.goto(LOGIN_URL, { timeout: 45_000, waitUntil: "domcontentloaded" });

    // Click "Login"
    console.error("➡️ Clicking Login button...");
    await page.getByRole("button", { name: /login/i }).click();

    // Fill username/password
    console.error("➡️ Filling credentials...");
    await page.getByRole("textbox", { name: /user name/i }).fill(USERNAME);
    await page.getByRole("textbox", { name: /password/i }).fill(PASSWORD);

    // Sign in
    console.error("➡️ Signing in...");
    await page.getByRole("button", { name: /sign in/i }).click();

    await page.locator('#log').waitFor({ state: 'visible', timeout: 100000 });


    // Navigate to logs (original script: #log → 'Save logs to file' flow)
    console.error("➡️ Opening logs panel...");
    await page.locator("#log").click();

    await expect(page.getByRole('link', { name: 'Save logs to file' })).toBeVisible();


    console.error("➡️ Clicking 'Save logs to file'...");
    await page.getByRole('link', { name: 'Save logs to file' }).click();

    await expect(page.locator('#savelog').getByText('Event log')).toBeVisible();


    // Select which logs to include
    console.error("➡️ Selecting event log and adjusting count...");
    await page.locator("#eventlog").check();

    // Set number of items (double-click then fill)
    const itemsInput = page.locator("#numofeventlogitems");
    await itemsInput.dblclick();
    await itemsInput.fill("1000");

    // Generate logs
    console.error("➡️ Generating logs...");
    await page.getByRole("button", { name: /generate log\(s\)/i }).click();

    // Wait for "Status: Complete!"
    console.error("⏳ Waiting for completion message...");
    await page.getByText(/Status:\s*Complete!/i).waitFor({ timeout: 60_000 });
    console.error("✅ Generation complete.");

    // Download log
    console.error("➡️ Downloading log file...");
    const [download] = await Promise.all([
      page.waitForEvent("download"),
      page.getByRole("button", { name: /download log/i }).click(),
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
