<?php

namespace App\Repositories\Front;

use App\Models\Product;
use App\Models\Category;
use App\Models\AttributeOption;
use App\Models\ProductAttributeValue;

use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;

class CatalogueRepository implements CatalogueRepositoryInterface
{
    public function paginate($perPage, $request)
    {
        $products = Product::active();

		$products = $this->searchProducts($products, $request);
		$products = $this->filterProductsByPriceRange($products, $request);
		$products = $this->filterProductsByAttribute($products, $request);
		$products = $this->sortProducts($products, $request);

		// $this->data['products'] = $products->paginate(9);
		return $products->paginate($perPage);
    }

    public function findBySlug($slug)
    {
        return Product::active()->where('slug', $slug)->firstOrFail();
    }

    public function getAttributeOptions($product, $attributeName)
    {
        // return ProductAttributeValue::getAttributeOptions($product, $attributeName)->pluck('text_value', 'text_value');
        return ProductAttributeValue::getAttributeOptions($product, $attributeName);
    }

    public function getParentCategories()
    {
        return Category::ParentCategories()
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function getAttributeFilters($attributeCode)
    {
        return AttributeOption::whereHas(
			'attribute',
			function ($query) use ($attributeCode) {
					$query->where('code', $attributeCode)
						->where('is_filterable', 1);
			}
		)
		->orderBy('name', 'asc')->get();
    }

    public function getMinPrice()
    {
        return Product::min('price');
    }

    public function getMaxPrice()
    {
        return Product::max('price');
    }

    // Private Method
    private function searchProducts($products, $request)
	{
		if ($q = $request->query('q')) {
			$q = str_replace('-', ' ', Str::slug($q));

			$products = $products->whereRaw('MATCH(name, slug, short_description, description) AGAINST (? IN NATURAL LANGUAGE MODE)', [$q]);

			$this->data['q'] = $q;
		}

		if ($categorySlug = $request->query('category')) {
			$category = Category::where('slug', $categorySlug)->firstOrFail();

			$childIds = Category::childIds($category->id);
			$categoryIds = array_merge([$category->id], $childIds);

			$products = $products->whereHas(
				'categories',
				function ($query) use ($categoryIds) {
					$query->whereIn('categories.id', $categoryIds);
				}
			);
		}

		return $products;
	}

    private function filterProductsByPriceRange($products, $request)
	{
		$lowPrice = null;
		$highPrice = null;

		if ($priceSlider = $request->query('price')) {
			$prices = explode('-', $priceSlider);

			$lowPrice = !empty($prices[0]) ? (float)$prices[0] : $this->data['minPrice'];
			$highPrice = !empty($prices[1]) ? (float)$prices[1] : $this->data['maxPrice'];

			if ($lowPrice && $highPrice) {
				$products = $products->where('price', '>=', $lowPrice)
					->where('price', '<=', $highPrice)
					->orWhereHas(
						'variants',
						function ($query) use ($lowPrice, $highPrice) {
							$query->where('price', '>=', $lowPrice)
								->where('price', '<=', $highPrice);
						}
					);

				$this->data['minPrice'] = $lowPrice;
				$this->data['maxPrice'] = $highPrice;
			}
		}

		return $products;
	}

    private function filterProductsByAttribute($products, $request)
	{
		if ($attributeOptionID = $request->query('option')) {
			$attributeOption = AttributeOption::findOrFail($attributeOptionID);

			$products = $products->whereHas(
				'ProductAttributeValues',
				function ($query) use ($attributeOption) {
					$query->where('attribute_id', $attributeOption->attribute_id)
						->where('text_value', $attributeOption->name);
				}
			);
		}

		return $products;
	}

    private function sortProducts($products, $request)
	{
		if ($sort = preg_replace('/\s+/', '', $request->query('sort'))) {
			$availableSorts = ['price', 'created_at'];
			$availableOrder = ['asc', 'desc'];
			$sortAndOrder = explode('-', $sort);

			$sortBy = strtolower($sortAndOrder[0]);
			$orderBy = strtolower($sortAndOrder[1]);

			if (in_array($sortBy, $availableSorts) && in_array($orderBy, $availableOrder)) {
				$products = $products->orderBy($sortBy, $orderBy);
			}

			$this->data['selectedSort'] = url('products?sort='. $sort);
		}

		return $products;
	}
}
