<?php

namespace App\Repositories\Admin;

// use App\Models\Product;
// use App\Models\Category;
// use App\Models\ProductImage;
use App\Models\Attribute;
use App\Models\AttributeOption;
// use App\Models\ProductAttributeValue;
// use App\Models\ProductInventory;

use App\Repositories\Admin\Interfaces\AttributeRepositoryInterface;

class AttributeRepository implements AttributeRepositoryInterface
{
    public function getConfigurableAttributes()
    {
        return Attribute::where('is_configurable', true)->get();
    }
}
