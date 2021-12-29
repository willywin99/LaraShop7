<?php

namespace App\Repositories\Admin;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ProductAttributeValue;
use App\Models\ProductInventory;

use Str;
use DB;

use App\Repositories\Admin\Interfaces\ProductRepositoryInterface;
use App\Repositories\Admin\Interfaces\AttributeRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    private $_attributeRepository;
    public function __construct(
        AttributeRepositoryInterface $attributeRepository
    )
    {
        $this->_attributeRepository = $attributeRepository;
    }
    public function paginate($perPage)
    {
        return Product::orderBy('name', 'ASC')->paginate(10);
    }

    public function create($params = [])
    {
        $params['slug'] = Str::slug($params['name']);

        $product = DB::transaction(
			function () use ($params) {
				$categoryIds = !empty($params['category_ids']) ? $params['category_ids'] : [];
				$product = Product::create($params);
				$product->categories()->sync($categoryIds);

				if ($params['type'] == 'configurable') {
					$this->_generateProductVariants($product, $params);
				}

				return $product;
			}
		);

        return $product;
    }

    public function findById($id)
    {
        return Product::findOrFail($id);
    }

    public function update($params = [], int $id)
    {
        $params['slug'] = Str::slug($params['name']);
        $product = Product::findOrFail($id);

        $saved = false;
        $saved = DB::transaction(function () use ($product, $params) {
            $categoryIds = !empty($params['category_ids']) ? $params['category_ids'] : [];
            $product->update($params);
            $product->categories()->sync($categoryIds);

            if ($product->type == 'configurable') {
                $this->_updateProductVariants($params);
            } else {
                ProductInventory::updateOrCreate(['product_id' => $product->id], ['qty' => $params['qty']]);
            }
            return true;
        });
        return $saved;
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        // dd($product->variants);
        if ($product->variants) {
            foreach($product->variants as $variant) {
                $variant->delete();
            }
        }
        return $product->delete();
    }

    public function addImage($id, $image)
    {
        $product = Product::findOrFail($id);

		// if ($request->has('image')) {
        // $image = $request->file('image');
        $name = $product->slug . '_' . time();
        $fileName = $name . '.' . $image->getClientOriginalExtension();

        $folder = ProductImage::UPLOAD_DIR. '/images';

        $filePath = $image->storeAs($folder . '/original', $fileName, 'public');

        $resizedImage = $this->_resizeImage($image, $fileName, $folder);

        $params = array_merge(
            [
                'product_id' => $product->id,
                'path' => $filePath,
            ],
            $resizedImage
        );

        // if (ProductImage::create($params)) {
        // 	Session::flash('success', 'Image has been uploaded');
        // } else {
        // 	Session::flash('error', 'Image could not be uploaded');
        // }

        return ProductImage::create($params);

    // 	return redirect('admin/products/' . $id . '/images');
    // }
    }

    public function statuses()
    {
        return Product::statuses();
    }

    public function types()
    {
        return Product::types();
    }

    public function findImageById($id)
    {
        return ProductImage::findOrFail($id);
    }

    public function removeImage($id)
    {
        $image = $this->findImageById($id);

        return $image->delete();
    }

    private function _generateProductVariants($product, $params)
	{
		// $configurableAttributes = $this->_getConfigurableAttributes();
		$configurableAttributes = $this->_attributeRepository->getConfigurableAttributes();

		$variantAttributes = [];
		foreach ($configurableAttributes as $attribute) {
			$variantAttributes[$attribute->code] = $params[$attribute->code];
		}

		$variants = $this->_generateAttributeCombinations($variantAttributes);

		if ($variants) {
			foreach ($variants as $variant) {
				$variantParams = [
					'parent_id' => $product->id,
					// 'user_id' => Auth::user()->id,
					'user_id' => $params['user_id'],
					'sku' => $product->sku . '-' .implode('-', array_values($variant)),
					'type' => 'simple',
					'name' => $product->name . $this->_convertVariantAsName($variant),
				];

				$variantParams['slug'] = Str::slug($variantParams['name']);

				$newProductVariant = Product::create($variantParams);

				$categoryIds = !empty($params['category_ids']) ? $params['category_ids'] : [];
				$newProductVariant->categories()->sync($categoryIds);

				$this->_saveProductAttributeValues($newProductVariant, $variant, $product->id);
			}
		}
	}

    private function _generateAttributeCombinations($arrays)
	{
		$result = [[]];
		foreach ($arrays as $property => $property_values) {
			$tmp = [];
			foreach ($result as $result_item) {
				foreach ($property_values as $property_value) {
					$tmp[] = array_merge($result_item, array($property => $property_value));
				}
			}
			$result = $tmp;
		}
		return $result;
	}

    private function _convertVariantAsName($variant)
	{
		$variantName = '';

		foreach (array_keys($variant) as $key => $code) {
			$attributeOptionID = $variant[$code];
			$attributeOption = AttributeOption::find($attributeOptionID);

			if ($attributeOption) {
				$variantName .= ' - ' . $attributeOption->name;
			}
		}

		return $variantName;
	}

    private function _saveProductAttributeValues($product, $variant, $parentProductID)
	{
		foreach (array_values($variant) as $attributeOptionID) {
			$attributeOption = AttributeOption::find($attributeOptionID);

			$attributeValueParams = [
				'parent_product_id' => $parentProductID,
				'product_id' => $product->id,
				'attribute_id' => $attributeOption->attribute_id,
				'text_value' => $attributeOption->name,
			];

			ProductAttributeValue::create($attributeValueParams);
		}
	}

    private function _updateProductVariants($params)
	{
		if ($params['variants']) {
			foreach ($params['variants'] as $productParams) {
				$product = Product::find($productParams['id']);
				$product->update($productParams);

				$product->status = $params['status'];
				$product->save();

				ProductInventory::updateOrCreate(['product_id' => $product->id], ['qty' => $productParams['qty']]);
			}
		}
	}

    private function _resizeImage($image, $fileName, $folder)
	{
		$resizedImage = [];

		$smallImageFilePath = $folder . '/small/' . $fileName;
		$size = explode('x', ProductImage::SMALL);
		list($width, $height) = $size;

		$smallImageFile = \Image::make($image)->fit($width, $height)->stream();
		if (\Storage::put('public/' . $smallImageFilePath, $smallImageFile)) {
			$resizedImage['small'] = $smallImageFilePath;
		}

		$mediumImageFilePath = $folder . '/medium/' . $fileName;
		$size = explode('x', ProductImage::MEDIUM);
		list($width, $height) = $size;

		$mediumImageFile = \Image::make($image)->fit($width, $height)->stream();
		if (\Storage::put('public/' . $mediumImageFilePath, $mediumImageFile)) {
			$resizedImage['medium'] = $mediumImageFilePath;
		}

		$largeImageFilePath = $folder . '/large/' . $fileName;
		$size = explode('x', ProductImage::LARGE);
		list($width, $height) = $size;

		$largeImageFile = \Image::make($image)->fit($width, $height)->stream();
		if (\Storage::put('public/' . $largeImageFilePath, $largeImageFile)) {
			$resizedImage['large'] = $largeImageFilePath;
		}

		$extraLargeImageFilePath  = $folder . '/xlarge/' . $fileName;
		$size = explode('x', ProductImage::EXTRA_LARGE);
		list($width, $height) = $size;

		$extraLargeImageFile = \Image::make($image)->fit($width, $height)->stream();
		if (\Storage::put('public/' . $extraLargeImageFilePath, $extraLargeImageFile)) {
			$resizedImage['extra_large'] = $extraLargeImageFilePath;
		}

		return $resizedImage;
	}
}
