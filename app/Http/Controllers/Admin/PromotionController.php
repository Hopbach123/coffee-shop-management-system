<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromotionRequest;
use App\Http\Requests\Admin\UpdatePromotionRequest;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';
        $promotions = Promotion::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.promotions.index', compact('promotions', 'search'));
    }

    public function create(): View
    {
        return view('admin.promotions.create');
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $promotion = new Promotion;
        $newImagePath = $this->storeImage($request);
        $promotion->fill($request->safe()->except(['status', 'image']));
        $promotion->status = $request->validated('status');
        $promotion->image = $newImagePath;

        try {
            $promotion->save();
        } catch (Throwable $exception) {
            $this->deleteManagedImage($newImagePath);

            throw $exception;
        }

        return redirect()->route('admin.promotions.index')->with('success', 'Đã tạo khuyến mãi.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.edit', compact('promotion'));
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $oldImagePath = $promotion->image;
        $newImagePath = $request->hasFile('image') ? $this->storeImage($request) : null;
        $promotion->fill($request->safe()->except(['status', 'image']));
        $promotion->status = $request->validated('status');
        if ($newImagePath !== null) {
            $promotion->image = $newImagePath;
        }

        try {
            $promotion->save();
        } catch (Throwable $exception) {
            $this->deleteManagedImage($newImagePath);

            throw $exception;
        }

        if ($newImagePath !== null && Promotion::isManagedImagePath($oldImagePath)) {
            $this->deleteManagedImage($oldImagePath);
        }

        return redirect()->route('admin.promotions.index')->with('success', 'Đã cập nhật khuyến mãi.');
    }

    public function toggleStatus(Promotion $promotion): RedirectResponse
    {
        $promotion->status = $promotion->status === 'active' ? 'inactive' : 'active';
        $promotion->save();

        return redirect()->route('admin.promotions.index')->with('success', 'Đã đổi trạng thái khuyến mãi.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('success', 'Đã xóa khuyến mãi.');
    }

    private function storeImage(StorePromotionRequest $request): ?string
    {
        /** @var UploadedFile|null $image */
        $image = $request->file('image');

        if ($image === null) {
            return null;
        }

        $path = $image->store('promotions', 'public');

        if ($path === false) {
            throw new \RuntimeException('Promotion image could not be stored.');
        }

        return $path;
    }

    private function deleteManagedImage(?string $path): void
    {
        if (Promotion::isManagedImagePath($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
