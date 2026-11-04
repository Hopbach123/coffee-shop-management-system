const panel = document.querySelector('[data-menu-sort]');
if (panel) {
    const original = JSON.parse(panel.querySelector('[data-sort-items]').textContent);
    let items = structuredClone(original);
    let dragging = null;
    let saving = false;
    let dirty = false;
    const list = panel.querySelector('[data-sort-list]');
    const message = panel.querySelector('[data-sort-message]');
    const siblings = (parent) => items.filter(item => item.parent_id === parent).sort((a, b) => a.sort_order - b.sort_order || a.id - b.id);
    const depth = (item, seen = new Set()) => {
        if (seen.has(item.id)) throw new Error('Cấu trúc có vòng lặp.');
        seen.add(item.id);
        if (item.parent_id === null) return 1;
        const parent = items.find(row => row.id === item.parent_id);
        if (!parent || parent.type !== 'group') throw new Error('Menu cha không hợp lệ.');
        return depth(parent, seen) + 1;
    };
    const move = (id, parent, before = null) => {
        if (saving) return;
        const backup = structuredClone(items);
        try {
            const item = items.find(row => row.id === id);
            const oldParent = item.parent_id;
            const target = siblings(parent).filter(row => row.id !== id);
            const index = before === null ? target.length : target.findIndex(row => row.id === before);
            if (index < 0) return;
            item.parent_id = parent;
            target.splice(index, 0, item);
            target.forEach((row, position) => { row.sort_order = position; });
            if (oldParent !== parent) siblings(oldParent).forEach((row, position) => { row.sort_order = position; });
            items.forEach(row => { if (depth(row) > 3) throw new Error('Cấu trúc vượt quá 3 cấp.'); });
            dirty = true;
            message.textContent = 'Có thay đổi chưa lưu.';
        } catch (error) {
            items = backup;
            message.textContent = error.message;
        }
        render();
        list.querySelector(`[data-sort-id="${id}"] button`)?.focus();
    };
    const button = (label, handler) => {
        const element = document.createElement('button');
        element.type = 'button';
        element.textContent = label;
        element.addEventListener('click', handler);
        return element;
    };
    const dropZone = (element, handler) => {
        element.addEventListener('dragover', event => {
            if (dragging === null || saving) return;
            event.preventDefault();
            event.stopPropagation();
            element.classList.add('menu-sort__drop');
        });
        element.addEventListener('dragleave', () => element.classList.remove('menu-sort__drop'));
        element.addEventListener('drop', event => {
            event.preventDefault();
            event.stopPropagation();
            element.classList.remove('menu-sort__drop');
            if (dragging !== null && !saving) handler(dragging);
            dragging = null;
        });
    };
    const render = () => {
        list.replaceChildren();
        const seen = new Set();
        const add = item => {
            if (seen.has(item.id)) return;
            seen.add(item.id);
            const row = document.createElement('li');
            row.dataset.sortId = String(item.id);
            const handle = button('↕', () => {});
            handle.draggable = true;
            handle.setAttribute('aria-label', `Kéo menu ${item.name}`);
            handle.addEventListener('dragstart', event => {
                dragging = item.id;
                event.dataTransfer.setData('text/plain', String(item.id));
            });
            handle.addEventListener('dragend', () => {
                dragging = null;
                panel.querySelectorAll('.menu-sort__drop').forEach(zone => zone.classList.remove('menu-sort__drop'));
            });
            const name = document.createElement('span');
            try { name.textContent = `${'↳ '.repeat(depth(item) - 1)}${item.name} (${item.type === 'group' ? 'Nhóm' : 'Liên kết'})`; }
            catch { name.textContent = `${item.name} — cần kiểm tra cấu trúc`; }
            const parent = document.createElement('select');
            parent.setAttribute('aria-label', `Menu cha của ${item.name}`);
            const options = [{ id: null, name: 'Menu gốc' }, ...items.filter(other => other.type === 'group' && other.id !== item.id)];
            options.forEach(option => {
                const node = document.createElement('option');
                node.value = option.id ?? '';
                node.textContent = option.name;
                node.selected = item.parent_id === option.id;
                parent.append(node);
            });
            parent.addEventListener('change', () => move(item.id, parent.value === '' ? null : Number(parent.value)));
            const up = button(`Lên`, () => {
                const group = siblings(item.parent_id);
                const index = group.findIndex(other => other.id === item.id);
                if (index > 0) move(item.id, item.parent_id, group[index - 1].id);
            });
            const down = button('Xuống', () => {
                const group = siblings(item.parent_id);
                const index = group.findIndex(other => other.id === item.id);
                if (index < group.length - 1) move(item.id, item.parent_id, group[index + 2]?.id ?? null);
            });
            up.setAttribute('aria-label', `Đưa ${item.name} lên`);
            down.setAttribute('aria-label', `Đưa ${item.name} xuống`);
            row.append(handle, name, parent, up, down);
            if (item.type === 'group') {
                const into = button('Vào nhóm', () => { message.textContent = 'Thả tay kéo menu vào nút này, hoặc chọn menu cha ở ô chọn.'; });
                dropZone(into, id => move(id, item.id));
                row.append(into);
            }
            dropZone(row, id => { if (id !== item.id) move(id, item.parent_id, item.id); });
            list.append(row);
            siblings(item.id).forEach(add);
        };
        siblings(null).forEach(add);
        items.forEach(add);
    };
    dropZone(panel.querySelector('[data-drop-root]'), id => move(id, null));
    panel.querySelector('[data-sort-reset]').addEventListener('click', () => {
        if (saving) return;
        items = structuredClone(original);
        dirty = false;
        message.textContent = 'Đã hủy thay đổi chưa lưu.';
        render();
    });
    panel.querySelector('[data-sort-save]').addEventListener('click', async () => {
        if (saving || !dirty) return;
        saving = true;
        panel.querySelectorAll('button, select').forEach(element => { element.disabled = true; });
        message.textContent = 'Đang lưu…';
        try {
            const response = await fetch(panel.dataset.url, {
                method: 'PATCH', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': panel.dataset.token },
                body: JSON.stringify({ expected_tree: panel.dataset.revision, items: items.map(({ id, parent_id, sort_order }) => ({ id, parent_id, sort_order })) }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message || 'Không thể lưu.');
            message.textContent = 'Đã lưu. Đang tải dữ liệu mới nhất…';
            window.location.reload();
        } catch (error) {
            items = structuredClone(original);
            dirty = false;
            render();
            message.textContent = `Chưa xác nhận lưu thành công: ${error.message} Đã khôi phục bản đã tải; hãy tải lại dữ liệu mới nhất trước khi thử lại.`;
        } finally {
            saving = false;
            panel.querySelectorAll('button, select').forEach(element => { element.disabled = false; });
        }
    });
    // CRUD forms must not submit a stale view while hierarchy save is in progress.
    document.addEventListener('submit', event => {
        if (saving) { event.preventDefault(); message.textContent = 'Vui lòng đợi lưu cấu trúc hoàn tất.'; }
    });
    render();
    panel.hidden = false;
}
