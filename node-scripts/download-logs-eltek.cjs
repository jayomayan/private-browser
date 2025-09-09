// node-scripts/download-logs-eltek.cjs
process.env.PLAYWRIGHT_BROWSERS_PATH = "/var/www/toolbox/.playwright-browsers";

const { chromium } = require("playwright");
const fs = require("fs");
const os = require("os");
const path = require("path");

// ---- CLI ARGS ----
// Usage: node node-scripts/download-logs-eltek.cjs 10.194.82.152 admin admin ./storage/app/exports
const args = process.argv.slice(2);
const IP = args[0] || "10.194.82.152";
const USERNAME = args[1] || "admin";
const PASSWORD = args[2] || "admin";
const OUTPUT_DIR = args[3] || process.cwd();

const LOGIN_URL = `http://${IP}/INDEX.HTM`;

(async () => {
  // Create writable logs directory
  const logDir = path.join(process.cwd(), "logs");
  fs.mkdirSync(logDir, { recursive: true });

  // Temp profile for more stable sessions
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), "chrome-profile-"));
  const context = await chromium.launchPersistentContext(userDataDir, {
    acceptDownloads: true,
    headless: true,
    slowMo: 100,
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
    console.log("🌐 Navigating to login page...");
    await page.goto(LOGIN_URL, { timeout: 45000, waitUntil: "domcontentloaded" });

    console.log("🔐 Clicking Login button...");
    await page.getByRole("button", { name: "Login" }).click();

    console.log("👤 Filling in username...");
    await page.getByRole("textbox", { name: "User name" }).fill(USERNAME);
    await page.getByRole("textbox", { name: "User name" }).press("Tab");

    console.log("🔑 Filling in password...");
    await page.getByRole("textbox", { name: "Password" }).fill(PASSWORD);
    await page.getByRole("textbox", { name: "Password" }).press("Enter");

    console.log("📁 Waiting for Logs menu to appear...");
    await page.locator("#log").waitFor({ state: "visible", timeout: 30000 });

    console.log('✅ Waiting for the chart to load.');

    await page.waitForSelector('#Webpower_logg_chart.data-ready', {
    state: 'attached',   // or "visible" if you want to wait for it to show
    timeout: 15000       // adjust timeout based on load time
    });

    console.log('✅ #Webpower_logg_chart is attached and data-ready.');
    //--
    console.log("📂 Hovering then clicking Logs menu...");
    await page.waitForTimeout(2000);
    await page.locator('#log').hover();
    await page.waitForTimeout(200);
    await page.locator('#log').click();
    await page.waitForTimeout(1000);  // Give it time to load
console.log("✅ Hovered and clicked Logs.");

    //---

    console.log('⏳ Waiting for log menu (#button_log_save)...');
    await page.locator('#button_log_save').waitFor({ state: 'visible', timeout: 10000 });
    console.log('✅ Logs submenu loaded.');

    const saveLogsLink = page.locator('#button_log_save');
    await saveLogsLink.waitFor({ state: 'visible', timeout: 10000 });
    await saveLogsLink.click();
    console.log('✅ button_log_save is now visible and clicked.');

    console.log('⏳ Waiting for .newform-wrapper to load...');
    await page.locator(".newform-wrapper").waitFor({ state: 'visible', timeout: 30000 });
    console.log('✅ .newform-wrapper is now visible.');

    console.log("✅ Save logs screen loaded. Configuring options...");

    console.log("📌 Checking 'Event log'...");
    const eventLogCheckbox = page.locator("#eventlog");
    await eventLogCheckbox.waitFor({ timeout: 5000 });
    await eventLogCheckbox.check();

    console.log("🔢 Filling in number of log items...");
    const logCountInput = page.locator("#numofeventlogitems");
    await logCountInput.fill("500");

    console.log("⚙️ Generating logs...");
    await page.locator('#requestlog').waitFor({ state: 'visible' });
    await page.locator('#requestlog').click();

    console.log("⏳ Waiting for generation to complete...");

    await page.waitForTimeout(10000);

    console.log("✅ Confirmed: Log generation complete.");

    console.log("📥 Preparing to download log file...");
    const downloadPromise = page.waitForEvent("download");

    console.log("⬇️ Clicking Download log button...");
    await page.getByRole("button", { name: "Download log" }).click();

    const download = await downloadPromise;
    const filename = path.join(OUTPUT_DIR, `logs-${Date.now()}.zip`);
    await download.saveAs(filename);
    console.log(`✅ Downloaded log file saved as: ${filename}`);

    await context.close();
    process.exit(0);
  } catch (err) {
    console.error("❌ Script failed:", err.message || err);

    const errorShot = path.join(logDir, "error.png");
    try {
      await page.screenshot({ path: errorShot });
      console.log(`📸 Screenshot of error state saved to: ${errorShot}`);
    } catch (screenshotErr) {
      console.error("⚠️ Failed to save screenshot:", screenshotErr.message);
    }

    await context.close();
    process.exit(1);
  }
})();
