export function initializeTableMenu() {
    const root = document.querySelector('[data-table-menu]');
    if (!root) return;
    let draft = [];
    let nextId = 1;
    let busy = false;
    let reading = false;
    let uncertain = false;
    const list = root.querySelector('[data-draft-lines]');
    const save = root.querySelector('[data-draft-save]');
    const message = root.querySelector('[data-menu-message]');
    const refresh = root.querySelector('[data-order-refresh]');
    const say = (text) => { message.textContent = text; };
    const money = (cents) => `${cents / 100n}.${String(cents % 100n).padStart(2, '0')}`;
    const cents = (price) => { const [whole, fraction = ''] = price.split('.'); return BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0').slice(0, 2)); };
    const validQuantity = (value) => /^\d+$/.test(value) && Number(value) >= 1 && Number(value) <= 2147483647;
    function controls() {
        root.querySelectorAll('[data-draft-add], [data-draft-lines] input, [data-draft-lines] button').forEach((control) => { control.disabled = busy || reading || uncertain; });
        save.disabled = busy || reading || uncertain || draft.length === 0;
        save.textContent = busy ? 'Đang lưu…' : 'Lưu từng món';
        refresh.disabled = busy || reading;
        refresh.textContent = reading ? 'Đang đọc đơn…' : 'Đọc lại đơn';
        root.setAttribute('aria-busy', String(busy || reading));
        const count = root.querySelector('[data-draft-count]');
        if (count) count.textContent = String(draft.length);
        const estimate = draft.reduce((sum, line) => sum + (validQuantity(line.quantity) ? cents(line.price) * BigInt(line.quantity) : 0n), 0n);
        root.querySelector('[data-draft-estimate]').textContent = `Tạm tính draft: ${money(estimate)} đ (backend xác nhận giá khi lưu)`;
    }
    function render() {
        list.replaceChildren();
        if (draft.length === 0) { const empty = document.createElement('p'); empty.textContent = 'Chưa có món chưa lưu.'; list.append(empty); }
        draft.forEach((line) => {
            const row = document.createElement('div'); row.className = 'table-menu-draft-line';
            const name = document.createElement('strong'); name.textContent = line.name; row.append(name);
            const quantityLabel = document.createElement('label'); quantityLabel.textContent = 'Số lượng';
            const quantity = document.createElement('input'); quantity.type = 'number'; quantity.min = '1'; quantity.max = '2147483647'; quantity.step = '1'; quantity.value = line.quantity; quantity.required = true;
            quantity.id = `draft-quantity-${line.id}`; quantityLabel.htmlFor = quantity.id;
            quantity.setAttribute('aria-label', `Số lượng ${line.name}`);
            quantity.addEventListener('input', () => { line.quantity = quantity.value; quantity.setAttribute('aria-invalid', String(!validQuantity(line.quantity))); controls(); });
            const quantityControls = document.createElement('div'); quantityControls.className = 'table-menu-quantity';
            const stepButton = (step, text) => {
                const button = document.createElement('button'); button.type = 'button'; button.className = 'coffee-button coffee-button--outline'; button.textContent = text;
                button.setAttribute('aria-label', `${step < 0 ? 'Giảm' : 'Tăng'} số lượng ${line.name}`);
                button.addEventListener('click', () => {
                    if (busy || reading || uncertain) return;
                    const current = validQuantity(line.quantity) ? Number(line.quantity) : 1;
                    line.quantity = String(Math.max(1, Math.min(2147483647, current + step)));
                    quantity.value = line.quantity; quantity.setAttribute('aria-invalid', 'false'); controls();
                });
                return button;
            };
            quantityControls.append(stepButton(-1, '−')); quantityControls.append(quantity); quantityControls.append(stepButton(1, '+'));
            row.append(quantityLabel); row.append(quantityControls);
            const noteLabel = document.createElement('label'); noteLabel.textContent = 'Ghi chú món';
            const note = document.createElement('input'); note.type = 'text'; note.maxLength = 500; note.value = line.note;
            note.addEventListener('input', () => { line.note = note.value; }); noteLabel.append(note); row.append(noteLabel);
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'coffee-button coffee-button--outline'; remove.textContent = 'Bỏ món chưa lưu';
            remove.setAttribute('aria-label', `Bỏ ${line.name} khỏi draft`);
            remove.addEventListener('click', () => {
                draft = draft.filter((candidate) => candidate.id !== line.id); render(); say('Đã bỏ món khỏi draft.');
                const target = list.querySelector?.('input') || (draft.length ? save : root.querySelector('[data-draft-add]'));
                target?.focus?.();
            }); row.append(remove); list.append(row);
        });
        controls();
    }
    function saved(snapshot) {
        const savedList = root.querySelector('[data-saved-lines]'); savedList.replaceChildren();
        if (!snapshot.items.length) { const empty = document.createElement('p'); empty.textContent = 'Đơn chưa có món đã lưu.'; savedList.append(empty); }
        snapshot.items.forEach((item) => { const row = document.createElement('p'); row.textContent = `${item.product_name} · ${item.variant_name || ''} × ${item.quantity} · Đơn giá ${item.unit_price} · Thành tiền ${item.subtotal} đ · ${item.status}${item.note ? ` — ${item.note}` : ''}`; savedList.append(row); });
        root.querySelector('[data-order-total]').textContent = `Tổng đơn theo backend: ${snapshot.total_amount} đ (giảm ${snapshot.discount_amount}, thuế ${snapshot.tax_amount})`;
    }
    root.querySelectorAll('[data-draft-add]').forEach((button) => {
        button.addEventListener('click', () => {
            if (busy || reading || uncertain) return;
            draft.push({ id: nextId++, variant_id: Number(button.dataset.variant), name: button.dataset.name, price: button.dataset.price, quantity: '1', note: '' });
            render(); say('Đã thêm vào draft, chưa lưu vào đơn.');
        });
    });
    save.addEventListener('click', async () => {
        if (busy || reading || uncertain || draft.length === 0) return;
        if (draft.some((line) => !validQuantity(line.quantity) || line.note.length > 500)) { say('Kiểm tra số lượng từ 1 đến 2147483647 và ghi chú tối đa 500 ký tự.'); return; }
        busy = true; controls(); let count = 0;
        try {
            while (draft.length) {
                const line = draft[0]; const abort = new AbortController();
                const timeout = setTimeout(() => abort.abort(), 20000);
                let response;
                try {
                    response = await fetch(root.dataset.saveUrl, { method: 'POST', credentials: 'same-origin', signal: abort.signal, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf }, body: JSON.stringify({ variant_id: line.variant_id, quantity: Number(line.quantity), note: line.note }) });
                } finally { clearTimeout(timeout); }
                if ([403, 404, 409, 419, 422].includes(response.status)) {
                    const result = await response.json();
                    say(`Đã lưu ${count} món. ${Object.values(result.errors || {}).flat().join(' ') || result.message || 'Không thể lưu món tiếp theo.'} Các món chưa xác nhận vẫn ở draft.`); return;
                }
                if (response.status !== 201) throw new Error('Uncertain save');
                const result = await response.json();
                if (!Number.isInteger(result.saved_item_id) || !Array.isArray(result.snapshot?.items)) throw new Error('Invalid acknowledgement');
                draft.shift(); count++; saved(result.snapshot); render();
                say(`Đã lưu ${count} món; còn ${draft.length} món chưa lưu.`);
            }
        } catch {
            uncertain = true;
            say(`Đã xác nhận lưu ${count} món. Kết quả request cuối chưa xác định; không gửi lại tự động. Đọc lại đơn để đối chiếu trước khi rời trang hoặc soạn lại. Draft được giữ và khóa để tránh gửi trùng.`);
        } finally { busy = false; render(); }
    });
    refresh.addEventListener('click', async () => {
        if (busy || reading) return;
        reading = true; controls(); say('Đang đọc dữ liệu đã lưu. Draft được giữ nguyên.');
        const abort = new AbortController();
        const timeout = setTimeout(() => abort.abort(), 20000);
        try {
            const response = await fetch(root.dataset.readUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: abort.signal });
            if (!response.ok) { say('Đơn hoặc bàn không còn cho phép gọi món, hoặc quyền truy cập đã thay đổi. Draft vẫn được giữ.'); return; }
            saved(await response.json()); say(uncertain ? 'Đã tải món đã lưu. Draft vẫn khóa do kết quả request trước chưa xác định; kiểm tra chi tiết đơn trước khi soạn lại.' : 'Đã tải lại đơn; draft chưa lưu vẫn được giữ.');
        } catch { say('Không thể tải lại đơn. Draft vẫn được giữ.'); }
        finally { clearTimeout(timeout); reading = false; controls(); }
    });
    root.querySelector('[data-cart-jump]')?.addEventListener('click', () => {
        const panel = root.querySelector('[data-cart-panel]');
        if (panel) { panel.open = true; panel.querySelector('summary')?.focus(); }
    });
    window.addEventListener('beforeunload', (event) => { if (draft.length || busy) { event.preventDefault(); event.returnValue = ''; } });
    render();
}
