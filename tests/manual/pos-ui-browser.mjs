import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { resolve, extname, sep } from 'node:path';
import assert from 'node:assert/strict';

// No packages, personal browser profile, application server or live business writes.
const workspace = resolve(import.meta.dirname, '../..');
const output = resolve(workspace, 'storage/app/task98-ui');
await mkdir(output, { recursive: true });
const mime = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.woff': 'font/woff', '.woff2': 'font/woff2' };
const server = createServer(async (request, response) => {
    if (request.method !== 'GET') { response.writeHead(405).end(); return; }
    const path = new URL(request.url, 'http://127.0.0.1:8798').pathname;
    const root = path.startsWith('/build/') ? resolve(workspace, 'public/build') : output;
    const relative = path.startsWith('/build/') ? path.slice(7) : path.startsWith('/fixtures/') ? path.slice(10) : '';
    const file = resolve(root, relative);
    if (!relative || !file.startsWith(root + sep)) { response.writeHead(404).end(); return; }
    try { response.writeHead(200, { 'Content-Type': mime[extname(file)] || 'application/octet-stream' }); response.end(await readFile(file)); }
    catch { response.writeHead(404).end(); }
});
await new Promise((done, fail) => { server.once('error', fail); server.listen(8798, '127.0.0.1', done); });
const profile = resolve(output, `edge-profile-${Date.now()}`);
const browser = spawn('C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', [
    '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking',
    '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank',
], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let socket;
const results = { viewports: [], interactions: [], errors: [] };
try {
    const address = await new Promise((done, fail) => {
        let stderr = '';
        const timeout = setTimeout(() => fail(new Error('Browser launch timed out')), 15000);
        browser.once('error', fail);
        browser.stderr.on('data', chunk => {
            stderr += chunk;
            const match = stderr.match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if (match) { clearTimeout(timeout); done(match[1]); }
        });
    });
    socket = new WebSocket(address);
    await new Promise((done, fail) => { socket.addEventListener('open', done, { once: true }); socket.addEventListener('error', fail, { once: true }); });
    let nextId = 0;
    const pending = new Map();
    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (message.id) {
            const promise = pending.get(message.id);
            if (promise) { pending.delete(message.id); message.error ? promise.reject(new Error(JSON.stringify(message.error))) : promise.resolve(message.result); }
        }
        if (message.method === 'Runtime.exceptionThrown') results.errors.push(message.params.exceptionDetails.text);
    });
    const command = (method, params = {}, sessionId) => new Promise((resolve, reject) => {
        const id = ++nextId;
        const timeout = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timeout: ${method}`)); }, 15000);
        pending.set(id, { resolve: result => { clearTimeout(timeout); resolve(result); }, reject });
        socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
    const target = await command('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await command('Target.attachToTarget', { targetId: target.targetId, flatten: true });
    const send = (method, params) => command(method, params, sessionId);
    await send('Page.enable'); await send('Runtime.enable');
    const evaluate = async expression => {
        const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
        return result.result.value;
    };
    const navigate = async page => {
        await send('Page.navigate', { url: `http://127.0.0.1:8798/fixtures/${page}.html` });
        for (let i = 0; i < 100; i++) {
            if (await evaluate(`document.readyState === 'complete' && location.pathname === '/fixtures/${page}.html'`)) return;
            await new Promise(done => setTimeout(done, 40));
        }
        throw new Error('Page did not load');
    };
    for (const width of [375, 768, 1024, 1440]) {
        await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: false });
        for (const page of ['areas', 'floor', 'detail', 'menu', 'checkout', 'estimate', 'floor-error', 'floor-empty']) {
            await navigate(page);
            const metrics = await evaluate(`({ width: innerWidth, scroll: document.documentElement.scrollWidth, title: document.title,
                targets: [...document.querySelectorAll('main :is(.coffee-button,input:not([type=hidden]),select,summary)')].filter(e=>e.getClientRects().length).map(e=>({text:e.textContent.trim(),height:e.getBoundingClientRect().height})),
                overflow: [...document.querySelectorAll('main *')].filter(e=>e.getBoundingClientRect().right>innerWidth+1).slice(0,8).map(e=>e.className) })`);
            results.viewports.push({ page, width, ...metrics });
            assert.ok(metrics.scroll <= width + 1, `${page} ${width}: horizontal overflow ${metrics.scroll}; ${metrics.overflow}`);
            assert.ok(metrics.targets.every(target => target.height >= 47), `${page} ${width}: small targets`);
            if (['checkout', 'estimate'].includes(page) && width <= 768) {
                const caption = await evaluate(`({caption:document.querySelector('.estimate-items caption').getBoundingClientRect().width,table:document.querySelector('.estimate-items').getBoundingClientRect().width})`);
                assert.ok(caption.caption >= caption.table - 2, `${page} ${width}: caption collapsed`);
            }
            if ([375, 1440].includes(width) && !page.startsWith('floor-')) {
                const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
                await writeFile(resolve(output, `${page}-${width}.png`), Buffer.from(screenshot.data, 'base64'));
            }
        }
    }
    await navigate('menu');
    await evaluate(`document.querySelector('[data-draft-add]').click(); document.querySelector('[data-draft-add]').click();
        document.querySelector('[data-draft-lines] input[type=text]').value='Giữ ghi chú';
        document.querySelector('[data-draft-lines] input[type=text]').dispatchEvent(new Event('input'));
        document.querySelector('.table-menu-quantity button:last-child').click();`);
    await send('Emulation.setDeviceMetricsOverride', { width: 375, height: 900, deviceScaleFactor: 1, mobile: false });
    const draft = await evaluate(`(() => { const panel=document.querySelector('[data-cart-panel]'); panel.open=false; document.querySelector('[data-cart-jump]').click(); return { count:document.querySelector('[data-draft-count]').textContent, note:document.querySelector('[data-draft-lines] input[type=text]').value, quantity:document.querySelector('[data-draft-lines] input[type=number]').value, open:panel.open, focus:document.activeElement.id, outline:getComputedStyle(document.activeElement).outlineWidth, overflow:document.documentElement.scrollWidth }; })()`);
    assert.equal(draft.count, '2'); assert.equal(draft.note, 'Giữ ghi chú'); assert.equal(draft.quantity, '2'); assert.equal(draft.open, true); assert.equal(draft.focus, 'draft-title'); assert.ok(draft.overflow <= 376);
    await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
    const reducedDuration = await evaluate(`getComputedStyle(document.querySelector('[data-draft-save]')).transitionDuration`);
    assert.ok(Number.parseFloat(reducedDuration) <= 0.001, `Reduced motion duration: ${reducedDuration}`);
    await send('Emulation.setEmulatedMedia', { features: [] });
    await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' });
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
    assert.equal(await evaluate(`document.querySelector('[data-cart-panel]').open`), false);
    results.interactions.push({ test: 'Resize, native disclosure keyboard, draft note/quantity retained, jump focus', result: 'PASS', draft });
    await evaluate(`document.querySelector('[data-cart-panel]').open=true;
        window.fixtureRequests=0; window.fetch=async()=>{ window.fixtureRequests++; throw new Error('Fixture offline'); };
        document.querySelector('[data-order-refresh]').click();`);
    await new Promise(done => setTimeout(done, 50));
    assert.equal(await evaluate('window.fixtureRequests'), 1);
    assert.match(await evaluate(`document.querySelector('[data-menu-message]').textContent`), /Không thể tải lại/);
    assert.equal(await evaluate(`document.querySelector('[data-draft-count]').textContent`), '2');
    await evaluate(`document.querySelector('[data-draft-save]').click()`);
    await new Promise(done => setTimeout(done, 50));
    assert.equal(await evaluate(`document.querySelector('[data-draft-save]').disabled`), true);
    assert.match(await evaluate(`document.querySelector('[data-menu-message]').textContent`), /chưa xác định/);
    results.interactions.push({ test: 'Synthetic offline read/save preserves and locks uncertain draft; no actual writes', result: 'PASS' });
    // Leave fixture with draft without firing native beforeunload navigation prompt.
    await send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});
    const printTarget = await command('Target.createTarget', { url: 'about:blank' });
    const printSession = (await command('Target.attachToTarget', { targetId: printTarget.targetId, flatten: true })).sessionId;
    await command('Page.enable', {}, printSession);
    await command('Page.navigate', { url: 'http://127.0.0.1:8798/fixtures/estimate.html' }, printSession);
    await new Promise(done => setTimeout(done, 400));
    await command('Emulation.setEmulatedMedia', { media: 'print' }, printSession);
    const printState = await command('Runtime.evaluate', { expression: `({controls:getComputedStyle(document.querySelector('.estimate-controls')).display, rows:getComputedStyle(document.querySelector('.estimate-items tr')).display, total:document.querySelector('.estimate-total').textContent, sidebar:!!document.querySelector('.sneat-sidebar')})`, returnByValue: true }, printSession);
    assert.equal(printState.result.value.controls, 'none'); assert.equal(printState.result.value.rows, 'table-row'); assert.equal(printState.result.value.sidebar, false); assert.match(printState.result.value.total, /123456.70/);
    const pdf = await command('Page.printToPDF', { printBackground: true, paperWidth: 8.27, paperHeight: 11.69 }, printSession);
    await writeFile(resolve(output, 'estimate-a4.pdf'), Buffer.from(pdf.data, 'base64'));
    results.interactions.push({ test: 'Print media keeps table rows and total, hides controls, has no sidebar; A4 PDF generated', result: 'PASS', state: printState.result.value });
    assert.deepEqual(results.errors, []);
    console.log(`PASS: ${results.viewports.length} viewport/page checks; ${results.interactions.length} interaction groups; no JS exceptions.`);
    await command('Browser.close');
} catch (error) {
    results.failure = error.message; console.error(error.stack); process.exitCode = 1;
} finally {
    await writeFile(resolve(output, 'browser-results.json'), JSON.stringify(results, null, 2));
    socket?.close(); browser.kill(); server.close();
}
