const { chromium } = require('playwright');

// HUAWEI logs
module.exports.GetHUAWEI = async function(opt) {
    // create broser
    const browser = await chromium.launch({ headless: process.env.HEADLESS == "true" ? true : false });
    // ignore https errors (for huawei rectifier)
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    // create page
    const page = await context.newPage();
    // set timeout
    page.setDefaultTimeout(parseInt(process.env.TIMEOUT));

    try {
        // get to VNT url
        await page.goto(opt.host);
        // wait for the page to load
        await page.waitForLoadState("load");
        await page.waitForLoadState("networkidle");
    } catch (error) {
        console.log(error);
        // close page
        page.close();
        // clsoe broser
        await browser.close();
        return error;
    }

    // click username
    await page.locator('#usrname').click();
    // fill username
    await page.locator('#usrname').fill(opt.user);
    // click password
    await page.locator('#string').click();
    // fill password
    await page.locator('#string').fill(opt.pass);
    // click login button
    await page.getByRole('button', { name: 'Log In' }).click();

    // wait for the page to load
    await page.waitForLoadState("load");
    //await page.waitForLoadState("networkidle");

    // try to download
    try { 
        // clock to query
        await page.locator('iframe[name="whole"]').contentFrame().locator('#header').contentFrame().locator('a').filter({ hasText: 'Query' }).click();
        // wait for the page to load
        await page.waitForLoadState("domcontentloaded");
        //await page.waitForLoadState("networkidle");

        // click export data
        await page.locator('iframe[name="whole"]').contentFrame().locator('#tree').contentFrame().getByRole('link', { name: 'Export Data' }).click();
        // wait for the page to load
        await page.waitForLoadState("domcontentloaded");

        // select all checkbox
        await page.locator('iframe[name="whole"]').contentFrame().locator('#content').contentFrame().getByRole('radio', { name: 'All' }).check();
        //await page.locator('iframe[name="whole"]').contentFrame().locator('#content').contentFrame().getByRole('radio', { name: 'Historical Alarm' }).check();

        // wait event for the download
        const downloadPromise = page.waitForEvent('download');
        // click download button
        await page.locator('iframe[name="whole"]').contentFrame().locator('#content').contentFrame().getByRole('button', { name: 'Export' }).click();
        // wait for the download to complete
        const download = await downloadPromise;

        // path to the file
        var fileloc = `${process.env.LOCFILE}/${opt.siteid}_${opt.type}_${Date.now()}_${download.suggestedFilename()}`;
        // save the file on speficed location
        await download.saveAs(fileloc);

        // wait for few sections
        await page.waitForTimeout(1000);

        await page.reload({ waitUntil: 'networkidle' });
    } catch (error) {
        // catch error
        console.log(error);
        // error in downloading
        var fileloc = "Cannot export log data";
    } finally {
        // wait for few sections
        await page.waitForTimeout(2000);

        // logout page
        page.once('dialog', dialog => {
            console.log(`Dialog message: ${dialog.message()}`);
            dialog.accept().catch(() => {});
        });
        await page.locator('iframe[name="whole"]').contentFrame().locator('#header').contentFrame().getByTitle('Logout').click();
        
        // wait for few sections
        await page.waitForTimeout(2500);
        
        // close page
        page.close();
        // clsoe broser
        await browser.close();
    }

    // return file location
    return fileloc;
}