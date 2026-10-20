<?php

namespace App\View\Components\Internal;

use App\Services\AdminMenuSidebarService;
use Illuminate\View\Component;
use Illuminate\View\View;

class AdminSidebar extends Component
{
    public array $tree;

    public bool $unavailable;

    public bool $recovery;

    public bool $recoveryActive;

    public function __construct(AdminMenuSidebarService $service)
    {
        $user = request()->user('web');
        $data = $service->build($user, request()->route()?->getName());
        $this->tree = $data['tree'];
        $this->unavailable = $data['unavailable'];
        $this->recovery = $service->routeAllowed($user, 'admin.menus.index');
        $this->recoveryActive = request()->routeIs('admin.menus.*') && ! in_array(true, array_column($this->tree, 'active'), true);
    }

    public function render(): View
    {
        return view('components.internal.admin-sidebar');
    }
}
