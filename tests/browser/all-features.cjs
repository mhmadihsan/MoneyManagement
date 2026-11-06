const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { spawn, spawnSync } = require('node:child_process');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const root = path.resolve(__dirname, '../..');
const offline = process.env.OFFLINE_ASSETS === '1';
const temp = fs.mkdtempSync('/private/tmp/managementMoney-qa-');
const output = path.join(root, offline ? 'docs/testing/offline-browser' : 'docs/testing/browser');
fs.mkdirSync(output, { recursive: true });
const database = path.join(temp, 'database.sqlite');
fs.writeFileSync(database, '');
const env = { ...process.env, APP_ENV: 'local', APP_URL: 'http://127.0.0.1:18084',
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', SESSION_DRIVER: 'file',
    CACHE_STORE: 'array', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync' };
for (const args of [['artisan', 'migrate', '--force'], ['tests/browser/seed.php']]) {
    const result = spawnSync('php', args, { cwd: root, env, encoding: 'utf8' });
    if (result.status !== 0) throw new Error(result.stdout + result.stderr);
}
const serverLog = fs.openSync(path.join(temp, 'server.log'), 'a');
const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=18084', '--tries=1', '--no-reload'],
    { cwd: root, env, stdio: ['ignore', serverLog, serverLog] });
const results = [];
const errors = [];
const networkFailures = [];
const consoleErrors = [];
const externalRequests = [];
const localAssetFailures = [];
let browser;
const base = 'http://127.0.0.1:18084';
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));

(async () => {
    try {
        let ready = false;
        for (let i = 0; i < 60; i++) {
            try { if ((await fetch(base + '/up')).ok) { ready = true; break; } } catch {}
            await pause(250);
        }
        assert(ready, 'QA server failed to start: ' + temp);
        browser = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: true });
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, serviceWorkers: 'block' });
        if (offline) {
            await context.route('**/*', route => {
                if (new URL(route.request().url()).origin !== base) {
                    externalRequests.push(route.request().url());
                    return route.abort('internetdisconnected');
                }
                return route.continue();
            });
            context.on('response', response => {
                if (response.status() >= 400 && /\/(vendor|build)\//.test(new URL(response.url()).pathname)) {
                    localAssetFailures.push({ url: response.url(), status: response.status() });
                }
            });
        }
        const page = await context.newPage();
        page.setDefaultTimeout(12000);
        page.on('pageerror', error => errors.push({ url: page.url(), message: error.message }));
        page.on('console', message => { if (message.type() === 'error') consoleErrors.push({ url: page.url(), message: message.text() }); });
        page.on('requestfailed', request => networkFailures.push({ url: request.url(), error: request.failure()?.errorText }));
        const go = url => page.goto(base + url, { waitUntil: 'load', timeout: 45000 });
        async function check(name, action) {
            try { await action(); results.push({ name, status: 'PASS' }); }
            catch (error) {
                results.push({ name, status: 'FAIL', message: error.message });
                await page.screenshot({ path: path.join(output, `failure-${results.length}.png`), fullPage: true }).catch(() => {});
            }
            console.log(results.at(-1).status + ' ' + name);
        }
        const text = selector => page.locator(selector).innerText();
        await check('Beranda dan tautan login', async () => {
            await go('/'); assert((await text('body')).includes('Management Keuangan'));
            await page.locator('a[href$="/login"]').first().click(); await page.waitForURL('**/login');
        });
        await check('Login salah menampilkan pesan', async () => {
            await go('/login'); await page.fill('#email', 'browser-qa@example.test'); await page.fill('#password', 'wrong');
            await page.locator('button[type=submit]').click(); await page.waitForLoadState('load');
            assert((await text('body')).includes('These credentials do not match our records.'));
        });
        await check('Login benar dan dashboard', async () => {
            await page.fill('#password', 'Browser-QA-2026!'); await page.locator('button[type=submit]').click();
            await page.waitForURL('**/dashboard'); assert((await text('body')).includes('Rp 12.000'));
        });
        await check('Grafik Chart.js aktif', async () => {
            await page.waitForFunction(() => window.Chart && Chart.getChart('financeChart'));
            assert.deepEqual(await page.evaluate(() => Chart.getChart('financeChart').data.datasets[0].data), [12000, 0]);
            await page.screenshot({ path: path.join(output, 'dashboard-desktop.png'), fullPage: true });
        });
        if (offline) await check('Font Poppins dan ikon Bootstrap tersedia offline', async () => {
            await page.evaluate(() => document.fonts.ready);
            assert(await page.evaluate(() => document.fonts.check('400 16px Poppins') && document.fonts.check('16px bootstrap-icons')));
            const fonts = await page.evaluate(() => [...document.fonts].filter(font => font.status === 'loaded').map(font => font.family));
            assert(fonts.includes('Poppins')); assert(fonts.includes('bootstrap-icons'));
            assert.equal(await page.locator('.navbar-custom').evaluate(element => getComputedStyle(element).backgroundColor), 'rgb(13, 110, 253)');
        });
        await check('Tambah kategori', async () => {
            await go('/kategori'); await page.fill('#nama', 'Kategori Browser QA');
            await page.locator('form[action$="/kategori"] button[type=submit]').click(); await page.waitForLoadState('load');
            assert((await text('body')).includes('Kategori berhasil ditambahkan'));
        });
        await check('Modal edit kategori', async () => {
            const row = page.locator('tr').filter({ hasText: 'Kategori Browser QA' });
            await row.locator('button[data-bs-toggle=modal]').click();
            const modal = page.locator('.modal.show'); await modal.waitFor({ state: 'visible' });
            await modal.locator('input[name=nama]').fill('Kategori QA Revisi');
            await modal.locator('button[type=submit]').click(); await page.waitForLoadState('load');
            assert((await text('body')).includes('Kategori QA Revisi'));
        });
        await check('Select2 mencari kategori dan format rupiah', async () => {
            await go('/transaksi/create'); await page.locator('.select2-selection').click();
            await page.locator('.select2-search__field').fill('QA Revisi');
            await page.locator('.select2-results__option').filter({ hasText: 'Kategori QA Revisi' }).click();
            await page.fill('#nominal_display', '125000');
            assert.equal(await page.inputValue('#nominal_display'), '125.000');
            assert.equal(await page.inputValue('#nominal'), '125000');
        });
        await check('Simpan transaksi uang masuk', async () => {
            await page.fill('#keterangan', 'Pemasukan Browser QA'); await page.locator('main button[type=submit]').click();
            await page.waitForURL('**/transaksi'); assert((await text('body')).includes('Transaksi berhasil disimpan'));
        });
        await check('Kategori terkait transaksi ditolak saat dihapus', async () => {
            await go('/kategori'); page.once('dialog', dialog => dialog.accept());
            await page.locator('tr').filter({ hasText: 'Kategori QA Revisi' }).locator('button[type=submit]').first().click();
            await page.waitForLoadState('load');
            assert((await text('.alert-danger')).includes('masih digunakan oleh transaksi'));
            assert.equal(await page.locator('tr').filter({ hasText: 'Kategori QA Revisi' }).count(), 1);
        });
        await check('Simpan uang keluar dan saldo terkini', async () => {
            await go('/transaksi/create'); assert((await text('body')).includes('Rp 137.000'));
            await page.selectOption('#tipe', 'keluar');
            const category = await page.locator('#kategori_id option').filter({ hasText: 'Kategori QA Revisi' }).getAttribute('value');
            await page.selectOption('#kategori_id', category); await page.fill('#nominal_display', '25000');
            await page.fill('#keterangan', 'Pengeluaran Browser QA'); await page.locator('main button[type=submit]').click();
            await page.waitForURL('**/transaksi'); assert((await text('body')).includes('Rp 112.000'));
        });
        await check('DataTables pencarian, pagination, dan sorting', async () => {
            await page.waitForSelector('#transaksiTable_filter input');
            assert((await text('#transaksiTable_info')).includes('14'));
            if (offline) {
                assert((await text('#transaksiTable_info')).includes('Menampilkan'));
                assert((await text('#transaksiTable_filter')).includes('Cari:'));
            }
            assert.equal(await page.locator('#transaksiTable tbody tr').count(), 10);
            await page.locator('#transaksiTable_next a').click();
            assert.equal(await page.locator('#transaksiTable tbody tr').count(), 4);
            await page.locator('#transaksiTable_filter input').fill('Pemasukan Browser QA');
            assert.equal(await page.locator('#transaksiTable tbody tr').count(), 1);
            await page.locator('#transaksiTable_filter input').fill('');
            await page.locator('#transaksiTable thead th').first().click();
            assert((await text('#transaksiTable tbody tr:first-child')).includes('01-01'));
        });
        await check('Filter tipe, kategori, tanggal dan reset', async () => {
            await go('/transaksi'); await page.selectOption('#tipe', 'keluar');
            const id = await page.locator('#kategori_id option').filter({ hasText: 'Kategori QA Revisi' }).getAttribute('value');
            await page.selectOption('#kategori_id', id);
            await page.fill('#start_date', new Date().getFullYear() + '-01-01');
            await page.fill('#end_date', new Date().getFullYear() + '-12-31');
            await page.locator('main form[method=GET] button[type=submit]').click(); await page.waitForLoadState('load');
            await page.waitForSelector('#transaksiTable_filter input');
            assert.equal(await page.locator('#transaksiTable tbody tr').count(), 1);
            assert((await text('#transaksiTable tbody')).includes('Pengeluaran Browser QA'));
            await page.getByRole('link', { name: 'Reset', exact: true }).click(); await page.waitForLoadState('load');
            await page.waitForSelector('#transaksiTable_filter input');
            assert.equal(await page.inputValue('#tipe'), ''); assert.equal(await page.inputValue('#kategori_id'), '');
        });
        await check('Edit transaksi dan simpan nominal baru', async () => {
            await page.locator('#transaksiTable_filter input').fill('Pengeluaran Browser QA');
            await page.locator('#transaksiTable tbody a[href$="/edit"]').click(); await page.waitForURL('**/edit');
            await page.fill('#nominal_display', '30000'); await page.locator('main button[type=submit]').click();
            await page.waitForURL('**/transaksi'); assert((await text('body')).includes('Rp 107.000'));
        });
        if (!offline) await check('Audit nominal pecahan rupiah mempertahankan nilai', async () => {
            await go('/transaksi/create'); await page.fill('#nominal_display', '123,45');
            assert.equal(await page.inputValue('#nominal'), '123.45');
        });
        await check('Navigasi mobile dan tampilan dashboard', async () => {
            await page.setViewportSize({ width: 390, height: 844 }); await go('/dashboard');
            await page.locator('.navbar-toggler').click(); await page.locator('#navbarNav.show').waitFor();
            assert(await page.getByRole('link', { name: 'Master Kategori', exact: true }).isVisible());
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
            await page.screenshot({ path: path.join(output, 'dashboard-mobile.png'), fullPage: true });
            await page.setViewportSize({ width: 1440, height: 1000 });
        });
        await check('Batal hapus transaksi mempertahankan data', async () => {
            await go('/transaksi'); await page.locator('#transaksiTable_filter input').fill('Pemasukan Browser QA');
            page.once('dialog', dialog => dialog.dismiss());
            await page.locator('#transaksiTable tbody button[type=submit]').click();
            assert((await text('#transaksiTable tbody')).includes('Pemasukan Browser QA'));
        });
        await check('Hapus transaksi dan kategori setelah kosong', async () => {
            for (const note of ['Pemasukan Browser QA', 'Pengeluaran Browser QA']) {
                await go('/transaksi'); await page.locator('#transaksiTable_filter input').fill(note);
                page.once('dialog', dialog => dialog.accept());
                await page.locator('#transaksiTable tbody button[type=submit]').click(); await page.waitForLoadState('load');
                assert((await text('body')).includes('Transaksi berhasil dihapus'));
            }
            await go('/kategori'); page.once('dialog', dialog => dialog.accept());
            await page.locator('tr').filter({ hasText: 'Kategori QA Revisi' }).locator('button[type=submit]').first().click();
            await page.waitForLoadState('load'); assert((await text('body')).includes('Kategori berhasil dihapus'));
        });
        await check('Update profil', async () => {
            await go('/profile'); await page.fill('#name', 'Browser QA Revisi');
            await page.locator('form').filter({ has: page.locator('#name') }).locator('button[type=submit]').click();
            await page.waitForLoadState('load'); assert.equal(await page.inputValue('#name'), 'Browser QA Revisi');
        });
        if (offline) await check('Font Figtree dan halaman konfirmasi password tersedia offline', async () => {
            await page.evaluate(() => document.fonts.ready);
            assert(await page.evaluate(() => [...document.fonts].some(font => font.family === 'Figtree' && font.status === 'loaded')));
            await go('/confirm-password'); assert(await page.locator('#password').isVisible());
            await go('/profile');
        });
        await check('Ubah password dari profil', async () => {
            await page.fill('#update_password_current_password', 'Browser-QA-2026!');
            await page.fill('#update_password_password', 'New-Browser-QA-2026!');
            await page.fill('#update_password_password_confirmation', 'New-Browser-QA-2026!');
            await page.locator('form').filter({ has: page.locator('#update_password_current_password') }).locator('button[type=submit]').click();
            await page.waitForLoadState('load'); assert.equal(await page.locator('.text-red-600').count(), 0);
        });
        await check('Logout dan login memakai password baru', async () => {
            await go('/dashboard'); await page.locator('form[action$="/logout"] button').click(); await page.waitForURL(base + '/');
            await go('/login'); await page.fill('#email', 'browser-qa@example.test'); await page.fill('#password', 'New-Browser-QA-2026!');
            await page.locator('button[type=submit]').click(); await page.waitForURL('**/dashboard');
        });
        await check('Modal hapus akun dan logout otomatis', async () => {
            await go('/profile'); await page.getByRole('button', { name: 'Delete Account', exact: true }).first().click();
            await page.locator('#password').fill('New-Browser-QA-2026!');
            await page.locator('form[action$="/profile"]').filter({ has: page.locator('#password') }).getByRole('button', { name: 'Delete Account', exact: true }).click();
            await page.waitForURL(base + '/'); await go('/dashboard'); assert(page.url().endsWith('/login'));
        });
        if (offline) await check('Tidak ada koneksi internet atau kegagalan aset dan JavaScript', async () => {
            assert.deepEqual(externalRequests, []);
            assert.deepEqual(localAssetFailures, []);
            assert.deepEqual(networkFailures, []);
            assert.deepEqual(errors, []);
            assert.deepEqual(consoleErrors, []);
        });
    } finally {
        if (browser) await browser.close();
        server.kill('SIGTERM');
        const report = { date: new Date().toISOString(), database, browser: 'Google Chrome headless', offline, results, errors, networkFailures, consoleErrors, externalRequests, localAssetFailures };
        fs.writeFileSync(path.join(output, 'results.json'), JSON.stringify(report, null, 2));
        console.log(JSON.stringify({ passed: results.filter(r => r.status === 'PASS').length, failed: results.filter(r => r.status === 'FAIL').length, errors: errors.length, networkFailures: networkFailures.length }));
        process.exitCode = results.some(r => r.status === 'FAIL') ? 1 : 0;
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
