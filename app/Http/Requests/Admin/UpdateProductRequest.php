<?php

namespace App\Http\Requests\Admin;

class UpdateProductRequest extends StoreProductRequest
{
    // Create and update share the same editable fields; slug is generated on create.
}
