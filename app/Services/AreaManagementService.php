<?php

namespace App\Services;

use App\Models\Area;
use DomainException;
use Illuminate\Support\Facades\DB;

class AreaManagementService
{
    public function delete(Area $area): void
    {
        DB::transaction(function () use ($area): void {
            $locked = Area::query()->lockForUpdate()->findOrFail($area->getKey());
            $table = $locked->cafeTables()->withTrashed()->lockForUpdate()->first(['id']);
            if ($table !== null) {
                throw new DomainException('Không thể xóa khu vực còn bàn liên kết, kể cả bàn đã xóa mềm.');
            }
            if (! $locked->delete()) {
                throw new DomainException('Không thể xóa khu vực.');
            }
        }, 3);
    }
}
