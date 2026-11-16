import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeEstimatePrint } from '../../resources/js/staff/estimate-print.js';

function controls() {
    const button = { disabled: false, addEventListener: (_, listener) => { button.click = listener; } };
    const status = { textContent: '' };
    const scope = { querySelector: (selector) => selector === '[data-estimate-print]' ? button : status };
    return { button, status, scope };
}
test('print action opens only the provided dialog and never claims physical success', () => {
    const { button, status, scope } = controls(); let dialogs = 0;
    initializeEstimatePrint(scope, () => { dialogs++; assert.equal(button.disabled, true); });
    button.click(); assert.equal(dialogs, 1); assert.equal(button.disabled, false);
    assert.match(status.textContent, /Chưa xác nhận/); assert.doesNotMatch(status.textContent, /Đã in thành công/);
});
test('print failure is reported without modifying or retrying transactions', () => {
    const { button, status, scope } = controls(); let calls = 0;
    initializeEstimatePrint(scope, () => { calls++; throw new Error('dialog unavailable'); });
    button.click(); assert.equal(calls, 1); assert.equal(button.disabled, false);
    assert.match(status.textContent, /Không thể mở/);
});
test('other pages without print controls have no side effects', () => {
    initializeEstimatePrint({ querySelector: () => null }, () => { assert.fail('Unexpected print'); });
});
