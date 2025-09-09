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
    await page.locator("#log").waitFor({ state: "visible", timeout: 10000 });

    console.log("📂 Clicking Logs menu...");
    await page.locator("#log").click();

    console.log("📂 Clicking Logs menu again (to fully expand if needed)...");
    await page.locator("#log").click();

    console.log("🔍 Waiting for 'Save logs to file' link by ID...");
    const saveLogsLink = page.locator("#button_log_save");
    await saveLogsLink.waitFor({ state: "visible", timeout: 10000 });

    console.log("💾 Clicking Save logs to file...");
    await saveLogsLink.click();

    console.log("✅ Save logs screen loaded. Configuring options...");

    console.log("📌 Checking 'Event log'...");
    const eventLogCheckbox = page.locator("#eventlog");
    await eventLogCheckbox.waitFor({ timeout: 5000 });
    await eventLogCheckbox.check();

    console.log("🔢 Filling in number of log items...");
    const logCountInput = page.locator("#numofeventlogitems");
    await logCountInput.click();
    await logCountInput.fill("1000");

    console.log("⚙️ Generating logs...");
    await page.getByRole("button", { name: "Generate log(s)" }).click();

    console.log("⏳ Waiting for generation to complete...");
    await page.locator("#progress").waitFor({ timeout: 15000 });
    const progressText = await page.locator("#progress").innerText();
    if (!progressText.includes("Status: Complete!")) {
      throw new Error("🚫 Log generation did not complete successfully");
    }

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
