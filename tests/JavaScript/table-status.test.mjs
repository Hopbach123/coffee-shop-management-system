import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeStatusRefresh } from '../../resources/js/admin/table-status.js';

test('GET refresh shows loading without intercepting navigation; bfcache reloads once', () => {
    const handlers = {};
    const message = { textContent: '' };
    const link = { addEventListener: (_, callback) => { handlers.click = callback; } };
    const panel = {
        querySelector: selector => selector === '[data-status-refresh]' ? link : message,
        setAttribute: (key, value) => { panel[key] = value; },
    };
    let reloads = 0;
    initializeStatusRefresh({ querySelector: () => panel }, { addEventListener: (name, fn) => { handlers[name] = fn; } }, () => reloads++);
    handlers.pageshow({ persisted: false });
    assert.equal(reloads, 0);
    handlers.click({ preventDefault: () => assert.fail('Must preserve normal GET') });
    assert.equal(panel['aria-busy'], 'true');
    assert.match(message.textContent, /Đang tải/);
    handlers.pageshow({ persisted: true });
    handlers.pageshow({ persisted: true });
    assert.equal(reloads, 1);
});

test('pages without status controls register no listeners', () => {
    initializeStatusRefresh({ querySelector: () => null }, { addEventListener: () => assert.fail('Unexpected listener') }, () => assert.fail('Unexpected reload'));
});
