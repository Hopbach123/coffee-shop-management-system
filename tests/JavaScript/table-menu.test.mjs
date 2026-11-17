import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeTableMenu } from '../../resources/js/staff/table-menu.js';

class Element {
    constructor() { this.children = []; this.listeners = {}; this.dataset = {}; this.textContent = ''; }
    append(child) { this.children.push(child); }
    replaceChildren() { this.children = []; }
    setAttribute(key, value) { (this.attributes ||= {})[key] = value; }
    addEventListener(name, handler) { this.listeners[name] = handler; }
    async emit(name) { return this.listeners[name]?.({ preventDefault() {} }); }
}
function setup(fetcher) {
    const fields = new Map(['[data-draft-lines]', '[data-draft-save]', '[data-menu-message]', '[data-draft-estimate]', '[data-saved-lines]', '[data-order-total]', '[data-order-refresh]'].map((key) => [key, new Element()]));
    const add = new Element(); add.dataset = { variant: '1', name: '<b>Latte</b>', price: '12.35' };
    const root = new Element(); root.dataset = { saveUrl: '/save', readUrl: '/read', csrf: 'test-only' };
    root.querySelector = (selector) => fields.get(selector);
    root.querySelectorAll = (selector) => selector === '[data-draft-add]' ? [add] : [];
    globalThis.document = { querySelector: () => root, createElement: () => new Element() };
    const events = {}; globalThis.window = { addEventListener: (name, handler) => { events[name] = handler; } };
    globalThis.fetch = fetcher;
    initializeTableMenu();
    return { add, save: fields.get('[data-draft-save]'), fields, events, root };
}
const ack = (id) => ({ status: 201, json: async () => ({ saved_item_id: id, snapshot: { items: [{ product_name: '<b>Latte</b>', variant_name: 'Large', quantity: 1, unit_price: '12.35', subtotal: '12.35', status: 'pending' }], total_amount: '12.35', discount_amount: '0.00', tax_amount: '0.00' } }) });

test('draft is memory only; partial save removes only acknowledged line', async () => {
    let calls = 0;
    const ui = setup(async () => ++calls === 1 ? ack(1) : { status: 422, json: async () => ({ errors: { variant_id: ['Unavailable'] } }) });
    await ui.add.emit('click'); await ui.add.emit('click');
    assert.equal(calls, 0);
    assert.match(ui.fields.get('[data-draft-estimate]').textContent, /24.70/);
    await ui.save.emit('click');
    assert.equal(calls, 2);
    assert.equal(ui.fields.get('[data-draft-lines]').children.length, 1);
    assert.match(ui.fields.get('[data-menu-message]').textContent, /Unavailable/);
    assert.equal(ui.fields.get('[data-saved-lines]').children[0].textContent.includes('<b>Latte</b>'), true);
    let warned = false; ui.events.beforeunload({ preventDefault() { warned = true; } }); assert.equal(warned, true);
});

test('quantity touch controls keep note and draft in memory without requests', async () => {
    const ui = setup(() => assert.fail('No request while editing'));
    await ui.add.emit('click');
    const row = ui.fields.get('[data-draft-lines]').children[0];
    const quantity = row.children[2];
    await quantity.children[2].emit('click');
    assert.match(ui.fields.get('[data-draft-estimate]').textContent, /24.70/);
    await quantity.children[0].emit('click');
    await quantity.children[0].emit('click');
    assert.equal(quantity.children[1].value, '1');
    assert.equal(quantity.children[1].attributes['aria-invalid'], 'false');
});

test('refresh locks duplicate GET and save until completion then preserves draft', async () => {
    let calls = 0; let resolveRead;
    const ui = setup(() => { calls++; return new Promise(resolve => { resolveRead = resolve; }); });
    await ui.add.emit('click');
    const refresh = ui.fields.get('[data-order-refresh]');
    const pending = refresh.emit('click');
    assert.equal(refresh.disabled, true);
    assert.equal(ui.save.disabled, true);
    await refresh.emit('click'); await ui.save.emit('click');
    assert.equal(calls, 1);
    resolveRead({ ok: true, json: async () => (await ack(1).json()).snapshot });
    await pending;
    assert.equal(refresh.disabled, false); assert.equal(ui.save.disabled, false);
    assert.equal(ui.fields.get('[data-draft-lines]').children.length, 1);
    assert.match(ui.fields.get('[data-draft-estimate]').textContent, /12.35/);
});

test('refresh network failure restores controls and keeps draft', async () => {
    const ui = setup(async () => { throw new Error('offline'); });
    await ui.add.emit('click'); await ui.fields.get('[data-order-refresh]').emit('click');
    assert.equal(ui.save.disabled, false);
    assert.equal(ui.fields.get('[data-draft-lines]').children.length, 1);
    assert.match(ui.fields.get('[data-menu-message]').textContent, /Không thể tải lại/);
});

test('network uncertainty locks retained draft and never resends on another click', async () => {
    let calls = 0; const ui = setup(async () => { calls++; throw new Error('network'); });
    await ui.add.emit('click'); await ui.save.emit('click'); await ui.save.emit('click');
    assert.equal(calls, 1); assert.equal(ui.save.disabled, true);
    assert.match(ui.fields.get('[data-menu-message]').textContent, /chưa xác định/);
    assert.equal(ui.fields.get('[data-draft-lines]').children.length, 1);
});

test('double click while saving emits one request and success clears draft warning', async () => {
    let calls = 0; let release;
    const ui = setup(() => { calls++; return new Promise((resolve) => { release = resolve; }); });
    await ui.add.emit('click'); const pending = ui.save.emit('click'); await ui.save.emit('click');
    assert.equal(calls, 1); release(ack(1)); await pending;
    assert.equal(ui.save.disabled, true);
    assert.match(ui.fields.get('[data-draft-estimate]').textContent, /0.00/);
    let warned = false; ui.events.beforeunload({ preventDefault() { warned = true; } }); assert.equal(warned, false);
});
