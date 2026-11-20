import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeCatalogView } from '../../resources/js/admin/catalog-view.js';

function fixture(preference = null, blocked = false) {
    const controls = { hidden: true };
    const panels = ['grid', 'table'].map(mode => ({ dataset: { catalogPanel: mode }, hidden: mode !== 'grid' }));
    const buttons = ['grid', 'table'].map(mode => ({ dataset: { catalogMode: mode }, attrs: {},
        setAttribute(name, value) { this.attrs[name] = value; },
        addEventListener(_, fn) { this.click = fn; },
    }));
    const root = { querySelector: () => controls, querySelectorAll: selector => selector === '[data-catalog-mode]' ? buttons : panels };
    const storage = { value: preference, getItem() { if (blocked) throw new Error('denied'); return this.value; }, setItem(_, value) { if (blocked) throw new Error('denied'); this.value = value; } };
    return { scope: { querySelector: () => root }, controls, panels, buttons, storage };
}

test('gallery is usable by default and table choice persists with correct pressed state', () => {
    const f = fixture();
    initializeCatalogView(f.scope, f.storage);
    assert.equal(f.controls.hidden, false);
    assert.deepEqual(f.panels.map(p => p.hidden), [false, true]);
    f.buttons[1].click();
    assert.deepEqual(f.panels.map(p => p.hidden), [true, false]);
    assert.deepEqual(f.buttons.map(b => b.attrs['aria-pressed']), ['false', 'true']);
    assert.equal(f.storage.value, 'table');
});
test('restores table preference and rejects unknown values', () => {
    for (const [value, hidden] of [['table', [true, false]], ['unexpected', [false, true]]]) {
        const f = fixture(value);
        initializeCatalogView(f.scope, f.storage);
        assert.deepEqual(f.panels.map(p => p.hidden), hidden);
    }
});
test('blocked storage never prevents catalog switching', () => {
    const f = fixture(null, true);
    assert.doesNotThrow(() => initializeCatalogView(f.scope, f.storage));
    assert.doesNotThrow(() => f.buttons[1].click());
    assert.deepEqual(f.panels.map(p => p.hidden), [true, false]);
});
test('non-catalog pages and empty catalogs keep controls absent or hidden', () => {
    assert.doesNotThrow(() => initializeCatalogView({ querySelector: () => null }, null));
    const f = fixture();
    const root = f.scope.querySelector();
    root.querySelectorAll = () => [];
    initializeCatalogView(f.scope, f.storage);
    assert.equal(f.controls.hidden, true);
});
