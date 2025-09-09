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

// Some firm timeouts
const T_SHORT = 10_000;
const T_MED = 30_000;
const T_LONG = 120_000;

const LOGIN_URL = `http://${IP}/INDEX.HTM`;
// If some firmware uses lowercase, try both:
const ALT_LOGIN_URL = `http://${IP}/index.html`;

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

  // --- helpers ---
  async function waitVisible(locator, timeout = T_SHORT, name = "") {
    console.error(`⏳ Waiting for ${name || locator}...`);
    await page.locator(locator).waitFor({ state: "visible", timeout });
    console.error(`✅ Visible: ${name || locator}`);
  }

  async function ensureLogsMenuOpen() {
    // Click the main "Logs" tile first (#log)
    await waitVisible("#log", T_MED, "#log");
    await page.locator("#log").click();
    console.error("✅ Clicked #log");

    // Some UIs require clicking the parent <li id="log_menu"> anchor to expand children
    const parentAnchor = page.locator("#log_menu > a");
    if (await parentAnchor.isVisible()) {
      await parentAnchor.click();
      console.error("✅ Expanded #log_menu submenu");
    } else {
      // Fallback hover & key nav if needed
      await page.locator("#log_menu").hover();
      await page.keyboard.press("ArrowDown");
      await page.keyboard.press("ArrowDown");
      console.error("↪️ Tried hover/ArrowDown to expose submenu");
    }
  }

  async function saveFailureArtifacts(prefix = "failure") {
    try {
      const screenshotPath = path.join(OUTPUT_DIR, `${prefix}-${Date.now()}.png`);
      fs.mkdirSync(OUTPUT_DIR, { recursive: true });
      await page.screenshot({ path: screenshotPath, fullPage: true });
      console.error(`🖼️ Saved screenshot: ${screenshotPath}`);
      const htmlPath = path.join(OUTPUT_DIR, `${prefix}-${Date.now()}.html`);
      await fs.promises.writeFile(htmlPath, await page.content(), "utf8");
      console.error(`📄 Saved HTML snapshot: ${htmlPath}`);
    } catch (e) {
      console.error("⚠️ Failed to capture artifacts:", e?.message || e);
    }
  }

  try {
    // --- 1) Login ---
    try {
      await page.goto(LOGIN_URL, { timeout: 45_000, waitUntil: "domcontentloaded" });
      console.error("✅ Opened login page (INDEX.HTM).");
    } catch {
      console.error("⚠️ INDEX.HTM failed, trying index.html...");
      await page.goto(ALT_LOGIN_URL, { timeout: 45_000, waitUntil: "domcontentloaded" });
      console.error("✅ Opened login page (index.html).");
    }

    await page.getByRole("button", { name: "Login" }).click();
    console.error("✅ Clicked Login button.");

    await page.getByRole("textbox", { name: "User name" }).fill(USERNAME);
    console.error("✅ Filled username.");

    await page.getByRole("textbox", { name: "Password" }).fill(PASSWORD);
    console.error("✅ Filled password.");

    await page.getByRole("textbox", { name: "Password" }).press("Enter");
    console.error("✅ Submitted login form.");

    // --- 2) Open Logs > Save logs to file ---
    await ensureLogsMenuOpen();

    // Wait for and click the specific submenu item by ID
    //await waitVisible("#button_log_save", T_MED, "#button_log_save");

    // Some firmware triggers a small navigation or content swap; cover both:
    console.error("🖱️ Clicking #button_log_save…");
    await Promise.race([
      (async () => {
        await Promise.all([
          page.waitForLoadState("domcontentloaded", { timeout: T_MED }).catch(() => {}),
          page.locator("#button_log_save").click(),
        ]);
      })(),
      (async () => {
        await page.locator("#button_log_save").click();
        // If no nav, ensure form shows up:
        await page.locator("#save-log-form, #savelog").waitFor({ state: "visible", timeout: T_MED });
      })(),
    ]);
    console.error("✅ Save logs view opened.");

    // --- 3) Configure and generate logs ---
    await waitVisible("#save-log-form, #savelog", T_MED, "Save Logs form");
    await waitVisible("#eventlog", T_MED, "#eventlog");

    await page.locator("#eventlog").check();
    console.error("✅ Checked 'eventlog'.");

    await page.locator("#numofeventlogitems").fill("1000");
    console.error("✅ Set number of event log items = 1000.");

    // Click Generate
    const generateBtn = page.getByRole("button", { name: "Generate log(s)" });
    await generateBtn.waitFor({ state: "visible", timeout: T_SHORT });
    await generateBtn.click();
    console.error("✅ Clicked 'Generate log(s)'.");

    // --- 4) Wait for completion (regex for robustness against timestamps) ---
    console.error("⏳ Waiting for status complete…");
    const statusRegex = /^Status:\s*Complete!/;
    await page.getByText(statusRegex).waitFor({ state: "visible", timeout: T_LONG });
    console.error("✅ Status: Complete!");

    // --- 5) Download ---
    console.error("➡️ Downloading log file...");
    const [download] = await Promise.all([
      page.waitForEvent("download", { timeout: T_MED }),
      page.getByRole("button", { name: "Download log" }).click(),
    ]);

    const suggested = download.suggestedFilename();
    const outPath = path.join(OUTPUT_DIR, suggested);

    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    await download.saveAs(outPath);
    console.error(`✅ Saved: ${outPath}`);

    // Print to STDOUT for Laravel to capture
    console.log(outPath);
  } catch (err) {
    console.error("❌ Script Error:", err?.message || err);
    await saveFailureArtifacts("export-logs-error");
    process.exitCode = 1;
  } finally {
    await context.close();
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }
})();
