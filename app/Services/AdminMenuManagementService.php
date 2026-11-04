<?php

namespace App\Services;

use App\Models\AdminMenu;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class AdminMenuManagementService
{
    public function treeFingerprint(iterable $menus): string
    {
        $fingerprints = [];
        foreach ($menus as $menu) {
            $fingerprints[$menu->id] = $this->fingerprint($menu);
        }
        ksort($fingerprints);

        return hash('sha256', json_encode($fingerprints, JSON_THROW_ON_ERROR));
    }

    public function reorder(array $data): void
    {
        app(AdminMenuWriteLock::class)->run(function () use ($data) {
            $menus = AdminMenu::orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reject = fn ($message) => throw ValidationException::withMessages(['menu' => $message]);
            if (! hash_equals($this->treeFingerprint($menus), $data['expected_tree'])) {
                $reject('Danh sách đã thay đổi. Vui lòng tải lại trang.');
            }
            $items = collect($data['items'])->keyBy('id');
            $ids = $items->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($ids !== $menus->keys()->sort()->values()->all() || $items->count() !== count($data['items'])) {
                $reject('Phải gửi đủ mọi menu, không trùng hoặc thêm ID.');
            }
            $oldGroups = [];
            $newGroups = [];
            $affected = [];
            foreach ($menus as $menu) {
                $item = $items[$menu->id];
                $parent = isset($item['parent_id']) ? (int) $item['parent_id'] : null;
                if ($parent !== null && (! isset($menus[$parent]) || $menus[$parent]->type !== 'group')) {
                    $reject('Menu cha phải tồn tại và có loại nhóm.');
                }
                $cursor = $menu->id;
                $seen = [];
                while ($cursor !== null) {
                    if (! isset($items[$cursor]) || isset($seen[$cursor]) || count($seen) >= 3) {
                        $reject('Cấu trúc bị vòng lặp hoặc vượt quá 3 cấp.');
                    }
                    $seen[$cursor] = true;
                    $cursor = isset($items[$cursor]['parent_id']) ? (int) $items[$cursor]['parent_id'] : null;
                }
                $oldGroups[$menu->parent_id ?? 0][] = $menu;
                $newGroups[$parent ?? 0][] = $item;
                if ($parent !== $menu->parent_id) {
                    $affected[$menu->parent_id ?? 0] = true;
                    $affected[$parent ?? 0] = true;
                }
                // Protected links and every current ancestor must keep their parent.
                if (in_array($menu->route_name, config('admin_menu.protected_routes'), true)) {
                    $node = $menu;
                    $walked = [];
                    while ($node && ! isset($walked[$node->id])) {
                        $walked[$node->id] = true;
                        $next = $items[$node->id]['parent_id'] ?? null;
                        if (($next === null ? null : (int) $next) !== $node->parent_id) {
                            $reject('Không được đổi cha của menu hệ thống hoặc tổ tiên của nó.');
                        }
                        $node = $menus->get($node->parent_id);
                    }
                }
            }
            foreach ($newGroups as $parent => &$siblings) {
                usort($siblings, fn ($a, $b) => [$a['sort_order'], $a['id']] <=> [$b['sort_order'], $b['id']]);
                $old = collect($oldGroups[$parent] ?? [])->sortBy(fn ($m) => [$m->sort_order, $m->id])->pluck('id')->all();
                if ($old !== array_map(fn ($item) => (int) $item['id'], $siblings)) {
                    $affected[$parent] = true;
                }
            }
            unset($siblings);
            // Only affected sibling groups are normalized; unrelated records stay byte-identical.
            foreach ($newGroups as $parent => $siblings) {
                if (! isset($affected[$parent])) {
                    continue;
                }
                foreach ($siblings as $order => $item) {
                    $menu = $menus[$item['id']];
                    $menu->parent_id = $parent === 0 ? null : $parent;
                    $menu->sort_order = $order;
                    if ($menu->isDirty()) {
                        $menu->save();
                    }
                }
            }
        });
    }

    public function fingerprint(AdminMenu $menu): string
    {
        return hash('sha256', json_encode($menu->getAttributes(), JSON_THROW_ON_ERROR));
    }

    public function mutate(int $id, string $action, array $data): void
    {
        app(AdminMenuWriteLock::class)->run(function () use ($id, $action, $data) {
            $menu = AdminMenu::whereKey($id)->lockForUpdate()->firstOrFail();
            if (! hash_equals($this->fingerprint($menu), $data['expected_fingerprint'])) {
                throw ValidationException::withMessages(['menu' => 'Menu đã thay đổi. Vui lòng tải lại trang trước khi thao tác.']);
            }
            $protected = in_array($menu->route_name, config('admin_menu.protected_routes'), true);
            $all = AdminMenu::select(['id', 'parent_id', 'route_name'])->get()->keyBy('id');
            $protectedBranch = $protected;
            foreach ($all as $node) {
                if (! in_array($node->route_name, config('admin_menu.protected_routes'), true)) {
                    continue;
                }
                $seen = [];
                while ($node && ! isset($seen[$node->id])) {
                    if ($node->id === $menu->id) {
                        $protectedBranch = true;
                        break;
                    }
                    $seen[$node->id] = true;
                    $node = $all->get($node->parent_id);
                }
            }
            if ($action === 'delete') {
                if ($protectedBranch || AdminMenu::where('parent_id', $id)->exists()) {
                    throw ValidationException::withMessages(['menu' => 'Không thể xóa menu được bảo vệ hoặc menu đang có con.']);
                }
                $menu->delete();
            } elseif ($action === 'status') {
                if ($protectedBranch && ! (bool) $data['is_active']) {
                    throw ValidationException::withMessages(['menu' => 'Không thể tắt menu hệ thống hoặc nhánh chứa menu hệ thống.']);
                }
                $menu->is_active = (bool) $data['is_active'];
                if ($menu->isDirty()) {
                    $menu->save();
                }
            } elseif ($action === 'update') {
                if ($protected && ($data['type'] !== $menu->type || ($data['route_name'] ?? null) !== $menu->route_name)) {
                    throw ValidationException::withMessages(['menu' => 'Không được đổi loại hoặc route của menu hệ thống.']);
                }
                if ($data['type'] === 'link' && ! $protected && ! in_array($data['route_name'], $this->allowedRoutes(), true)) {
                    throw ValidationException::withMessages(['route_name' => 'Route không được phép sử dụng.']);
                }
                if ($data['type'] === 'link' && AdminMenu::where('parent_id', $id)->exists()) {
                    throw ValidationException::withMessages(['type' => 'Menu có con phải giữ loại nhóm.']);
                }
                if ($data['type'] === 'group') {
                    $data['route_name'] = null;
                }
                $menu->fill(collect($data)->only(['name', 'type', 'route_name', 'icon'])->all());
                $menu->save();
            } else {
                throw new \InvalidArgumentException('Unsupported menu mutation.');
            }
        });
    }

    public function allowedRoutes(): array
    {
        return array_values(array_filter(config('admin_menu.routes'), function ($name) {
            $route = Route::getRoutes()->getByName($name);

            return ! in_array($name, config('admin_menu.protected_routes'), true)
                && $route && in_array('GET', $route->methods(), true)
                && $route->parameterNames() === [] && $route->getDomain() === null
                && in_array('auth:web', $route->gatherMiddleware(), true)
                && in_array('role:admin', $route->gatherMiddleware(), true);
        }));
    }

    public function rows(): array
    {
        $menus = AdminMenu::ordered()->get();
        // Reuse the management page snapshot for its Sidebar in this request only.
        request()->attributes->set('admin_menu.source', $menus);
        $byId = $menus->keyBy('id');
        $children = $menus->groupBy(fn ($menu) => $menu->parent_id ?? 0);
        $seen = [];
        $rows = [];
        $visit = function ($menu, int $depth, bool $invalid = false) use (&$visit, &$rows, &$seen, $byId, $children): void {
            if (isset($seen[$menu->id])) {
                return;
            }
            $seen[$menu->id] = true;
            $rows[] = ['menu' => $menu, 'depth' => $depth, 'invalid' => $invalid || $depth > 3,
                'parent_name' => $byId->get($menu->parent_id)?->name, 'fingerprint' => $this->fingerprint($menu)];
            foreach ($children->get($menu->id, collect()) as $child) {
                $visit($child, $depth + 1, $invalid || $menu->type !== 'group');
            }
        };
        foreach ($children->get(0, collect()) as $root) {
            $visit($root, 1);
        }
        // Expose corrupt/orphan/cyclic records for management instead of dropping them.
        foreach ($menus as $menu) {
            if (! isset($seen[$menu->id])) {
                $visit($menu, 1, true);
            }
        }

        return $rows;
    }

    public function create(array $data): AdminMenu
    {
        return app(AdminMenuWriteLock::class)->run(function () use ($data) {
            if ($data['type'] === 'link' && ! in_array($data['route_name'], $this->allowedRoutes(), true)) {
                throw ValidationException::withMessages(['route_name' => 'Route không còn được phép sử dụng.']);
            }
            $parentId = $data['parent_id'] ?? null;
            $cursor = $parentId;
            $seen = [];
            while ($cursor !== null) {
                $parent = AdminMenu::whereKey($cursor)->lockForUpdate()->first();
                if (! $parent || $parent->type !== 'group' || isset($seen[$cursor]) || count($seen) >= 2) {
                    throw ValidationException::withMessages(['parent_id' => 'Menu cha không hợp lệ hoặc vượt giới hạn 3 cấp.']);
                }
                $seen[$cursor] = true;
                $cursor = $parent->parent_id;
            }
            $menu = new AdminMenu;
            $menu->fill(collect($data)->only(['name', 'type', 'route_name', 'icon'])->all());
            $menu->parent_id = $parentId;
            $menu->sort_order = $data['sort_order'] ?? 0;
            $menu->save();

            return $menu;
        });
    }
}
