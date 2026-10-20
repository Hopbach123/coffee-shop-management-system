<?php

namespace App\Services;

use App\Models\AdminMenu;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;

class AdminMenuSidebarService
{
    public function routeAllowed(?User $user, ?string $name): bool
    {
        if ($user?->role !== 'admin' || ! in_array($name, array_merge(config('admin_menu.routes'), config('admin_menu.protected_routes')), true)) {
            return false;
        }
        $route = Route::getRoutes()->getByName($name);
        if (! $route || ! in_array('GET', $route->methods(), true) || $route->parameterNames() !== [] || $route->getDomain() !== null) {
            return false;
        }
        $middleware = $route->gatherMiddleware();

        // Current permission source is auth:web + CheckRole. Unknown extra policy fails closed.
        return in_array('auth:web', $middleware, true) && in_array('role:admin', $middleware, true)
            && array_diff($middleware, ['web', 'auth:web', 'role:admin']) === [];
    }

    public function build(?User $user, ?string $currentRoute): array
    {
        if ($user?->role !== 'admin') {
            return ['tree' => [], 'unavailable' => false];
        }
        try {
            $menus = request()->attributes->get('admin_menu.source');
            if (! $menus instanceof Collection) {
                $menus = AdminMenu::ordered()->get(['id', 'parent_id', 'name', 'type', 'route_name', 'icon', 'sort_order', 'is_active', 'permission_key']);
            }
        } catch (QueryException) {
            return ['tree' => [], 'unavailable' => true];
        }
        $children = $menus->groupBy(fn ($menu) => $menu->parent_id ?? 0);
        $seen = [];
        $build = function ($parent, int $depth) use (&$build, &$seen, $children, $user): array {
            if ($depth > 3) {
                return [];
            }
            $result = [];
            foreach ($children->get($parent, collect()) as $menu) {
                if (isset($seen[$menu->id])) {
                    continue;
                }
                $seen[$menu->id] = true;
                if (! $menu->is_active || $menu->permission_key !== null) {
                    continue;
                }
                $node = ['id' => $menu->id, 'name' => $menu->name, 'type' => $menu->type,
                    'icon' => in_array($menu->icon, config('admin_menu.icons'), true) ? $menu->icon : null,
                    'route' => $menu->route_name, 'url' => null, 'children' => [], 'active' => false];
                if ($menu->type === 'group') {
                    if ($menu->route_name !== null) {
                        continue;
                    }
                    $node['children'] = $build($menu->id, $depth + 1);
                    if ($node['children'] === []) {
                        continue;
                    }
                } elseif ($menu->type === 'link' && $this->routeAllowed($user, $menu->route_name)) {
                    $node['url'] = route($menu->route_name);
                } else {
                    continue;
                }
                $result[] = $node;
            }

            return $result;
        };
        $tree = $build(0, 1);
        // Exact match takes precedence over module patterns, then stable traversal picks one duplicate.
        $choose = function (array $nodes, bool $exact) use (&$choose, $currentRoute): ?int {
            foreach ($nodes as $node) {
                if ($node['type'] === 'link' && ($exact ? $node['route'] === $currentRoute
                    : in_array($currentRoute, config('admin_menu.active_routes', [])[$node['route']] ?? [], true))) {
                    return $node['id'];
                }
                $child = $choose($node['children'], $exact);
                if ($child !== null) {
                    return $child;
                }
            }

            return null;
        };
        $activeId = $choose($tree, true) ?? $choose($tree, false);
        $mark = function (array $nodes) use (&$mark, $activeId): array {
            foreach ($nodes as &$node) {
                $node['children'] = $mark($node['children']);
                $node['active'] = $node['id'] === $activeId || in_array(true, array_column($node['children'], 'active'), true);
            }

            return $nodes;
        };

        return ['tree' => $mark($tree), 'unavailable' => false];
    }
}
