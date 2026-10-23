<?php

namespace App\Http\Requests\Admin;

class UpdateProductVariantRequest extends StoreProductVariantRequest
{
    // The parent request ignores the bound variant for both unique checks.
}
