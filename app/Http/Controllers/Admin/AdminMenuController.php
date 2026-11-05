<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MutateAdminMenuRequest;
use App\Http\Requests\Admin\ReorderAdminMenuRequest;
use App\Http\Requests\Admin\StoreAdminMenuRequest;
use App\Http\Requests\Admin\UpdateAdminMenuRequest;
use App\Models\AdminMenu;
use App\Services\AdminMenuManagementService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminMenuController extends Controller
{
    public function reorder(ReorderAdminMenuRequest $request, AdminMenuManagementService $service): JsonResponse
    {
        try {
            $service->reorder($request->validated());
        } catch (QueryException) {
            return response()->json(['message' => 'Không thể lưu cấu trúc menu. Dữ liệu chưa được cập nhật.'], 503);
        }

        return response()->json(['message' => 'Đã lưu cấu trúc menu.']);
    }

    public function edit(AdminMenu $admin_menu, AdminMenuManagementService $service): View
    {
        $routes = $service->allowedRoutes();
        if (in_array($admin_menu->route_name, config('admin_menu.protected_routes'), true)) {
            $routes[] = $admin_menu->route_name;
        }

        return view('admin.menus.edit', ['menu' => $admin_menu, 'routes' => $routes,
            'icons' => config('admin_menu.icons'), 'fingerprint' => $service->fingerprint($admin_menu)]);
    }

    public function update(UpdateAdminMenuRequest $request, AdminMenu $admin_menu, AdminMenuManagementService $service): RedirectResponse
    {
        return $this->mutation($admin_menu, 'update', $request->validated(), $service);
    }

    public function status(MutateAdminMenuRequest $request, AdminMenu $admin_menu, AdminMenuManagementService $service): RedirectResponse
    {
        return $this->mutation($admin_menu, 'status', $request->validated(), $service);
    }

    public function destroy(MutateAdminMenuRequest $request, AdminMenu $admin_menu, AdminMenuManagementService $service): RedirectResponse
    {
        return $this->mutation($admin_menu, 'delete', $request->validated(), $service);
    }

    private function mutation(AdminMenu $menu, string $action, array $data, AdminMenuManagementService $service): RedirectResponse
    {
        try {
            $service->mutate($menu->id, $action, $data);
        } catch (QueryException) {
            return back()->withErrors(['menu' => 'Không thể thay đổi menu do lỗi lưu trữ hoặc ràng buộc dữ liệu.'])
                ->withInput(collect($data)->only(['name', 'type', 'route_name', 'icon'])->all());
        }

        return redirect()->route('admin.menus.index')->with('success', match ($action) {
            'update' => 'Đã cập nhật menu.', 'status' => 'Đã lưu trạng thái menu. Sidebar hiện tại giữ nguyên.',
            'delete' => 'Đã xóa menu.',
        });
    }

    public function index(AdminMenuManagementService $service): View|Response
    {
        try {
            $rows = $service->rows();

            return view('admin.menus.index', ['rows' => $rows, 'treeFingerprint' => $service->treeFingerprint(array_column($rows, 'menu')),
                'sortItems' => array_map(fn ($row) => $row['menu']->only(['id', 'name', 'type', 'parent_id', 'sort_order']), $rows)]);
        } catch (QueryException) {
            return response()->view('admin.menus.unavailable', [], 503);
        }
    }

    public function create(AdminMenuManagementService $service): View|Response
    {
        try {
            $parents = array_filter($service->rows(), fn ($row) => ! $row['invalid'] && $row['depth'] < 3 && $row['menu']->type === 'group');

            return view('admin.menus.create', ['parents' => $parents, 'routes' => $service->allowedRoutes(), 'icons' => config('admin_menu.icons')]);
        } catch (QueryException) {
            return response()->view('admin.menus.unavailable', [], 503);
        }
    }

    public function store(StoreAdminMenuRequest $request, AdminMenuManagementService $service): RedirectResponse
    {
        try {
            $service->create($request->validated());
        } catch (QueryException) {
            return back()->withErrors(['menu' => 'Không thể lưu menu. Vui lòng kiểm tra kết nối và cấu hình database.'])
                ->withInput($request->safe()->only(['name', 'type', 'route_name', 'icon', 'parent_id', 'sort_order']));
        }

        return redirect()->route('admin.menus.index')->with('success', 'Đã thêm menu. Sidebar hiện tại chưa thay đổi.');
    }
}
