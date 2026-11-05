<?php

namespace App\Http\Requests\Admin;

use App\Services\AdminMenuManagementService;
use Illuminate\Validation\Rule;

class UpdateAdminMenuRequest extends StoreAdminMenuRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $routes = app(AdminMenuManagementService::class)->allowedRoutes();
        $menu = $this->route('admin_menu');
        if (in_array($menu->route_name, config('admin_menu.protected_routes'), true)) {
            $routes[] = $menu->route_name;
        }
        $rules['route_name'] = ['nullable', 'required_if:type,link', 'prohibited_if:type,group', 'string', 'max:150', Rule::in($routes)];
        $rules['parent_id'] = ['prohibited'];
        $rules['sort_order'] = ['prohibited'];
        $rules['created_at'] = ['prohibited'];
        $rules['expected_fingerprint'] = ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'];

        return $rules;
    }
}
