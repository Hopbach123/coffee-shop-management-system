import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/internal.js', import.meta.url), 'utf8');

function mount({ mobile = false, stored = null, denyStorage = false } = {}) {
    const listeners = new Map();
    const element = () => ({
        inert: false, attributes: {},
        addEventListener(type, callback) { this[type] = callback; },
        setAttribute(name, value) { this.attributes[name] = value; },
        focus() { document.activeElement = this; },
        getClientRects() { return [{}]; },
    });
    const classes = () => ({
        values: new Set(),
        toggle(name, enabled) { enabled ? this.values.add(name) : this.values.delete(name); },
    });
    const toggle = element();
    const close = element();
    const last = element();
    const sidebar = Object.assign(element(), {
        querySelectorAll: () => [close, last],
        contains: target => target === close || target === last,
    });
    const workspace = element();
    const backdrop = element();
    const shell = Object.assign(element(), {
        classList: classes(),
        querySelector: name => name === '.sneat-workspace' ? workspace : close,
    });
    const media = { matches: mobile, addEventListener: (event, callback) => { media.change = callback; } };
    const document = {
        activeElement: null,
        body: { classList: classes() },
        getElementById: () => sidebar,
        querySelector: selector => ({ '[data-sneat-layout]': shell, '[data-sneat-toggle]': toggle, '[data-sneat-backdrop]': backdrop })[selector],
        addEventListener: (event, callback) => listeners.set(event, callback),
    };
    const storage = new Map([['maison.sidebar.collapsed', stored]]);
    const localStorage = {
        getItem: key => { if (denyStorage) throw Error('denied'); return storage.get(key); },
        setItem: (key, value) => { if (denyStorage) throw Error('denied'); storage.set(key, value); },
    };
    vm.runInNewContext(source, { document, window: { matchMedia: () => media }, localStorage });
    return { shell, toggle, close, last, sidebar, workspace, backdrop, media, document, listeners, storage };
}

test('desktop collapse persists and does not make workspace inert', () => {
    const app = mount();
    assert.equal(app.toggle.attributes['aria-expanded'], 'true');
    app.toggle.click();
    assert.equal(app.sidebar.inert, true);
    assert.equal(app.workspace.inert, false);
    assert.equal(app.storage.get('maison.sidebar.collapsed'), 'true');
    app.toggle.click();
    assert.equal(app.sidebar.inert, false);
    assert.equal(app.toggle.attributes['aria-expanded'], 'true');
});

test('mobile drawer traps focus, Escape restores it and clears inert', () => {
    const app = mount({ mobile: true, stored: 'true' });
    assert.equal(app.sidebar.inert, true);
    app.toggle.click();
    assert.equal(app.document.activeElement, app.close);
    assert.equal(app.workspace.inert, true);
    app.last.focus();
    let prevented = false;
    app.listeners.get('keydown')({ key: 'Tab', shiftKey: false, preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(app.document.activeElement, app.close);
    app.listeners.get('keydown')({ key: 'Escape', preventDefault() {} });
    assert.equal(app.document.activeElement, app.toggle);
    assert.equal(app.workspace.inert, false);
    assert.equal(app.sidebar.inert, true);
});

test('resize exits modal mode; blocked localStorage does not break navigation', () => {
    const app = mount({ mobile: true, denyStorage: true });
    app.toggle.click();
    app.media.matches = false;
    app.media.change();
    assert.equal(app.workspace.inert, false);
    assert.equal(app.sidebar.inert, false);
    app.toggle.click();
    assert.equal(app.sidebar.inert, true);
});
