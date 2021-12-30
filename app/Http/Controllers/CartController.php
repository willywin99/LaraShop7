<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// use App\Models\Product;
// use App\Models\ProductInventory;

use App\Repositories\Front\Interfaces\CartRepositoryInterface;
use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;

/**
 * CartController
 *
 * PHP version 7
 *
 * @category CartController
 * @package  CartController
 * @author   Sugiarto <sugiarto.dlingo@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://localhost/
 */
class CartController extends Controller
{
    private $cartRepository;
    private $catalogueRepository;
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(
        CartRepositoryInterface $cartRepository,
        CatalogueRepositoryInterface $catalogueRepository
    )
	{
		parent::__construct();

        $this->cartRepository = $cartRepository;
        $this->catalogueRepository = $catalogueRepository;
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		// $items = \Cart::getContent();
		// $this->data['items'] =  $items;
		$this->data['items'] =  $this->cartRepository->getContent();

		return $this->loadTheme('carts.index', $this->data);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param \Illuminate\Http\Request $request request form
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$params = $request->except('_token');

		// $product = Product::findOrFail($params['product_id']);

        $product = $this->catalogueRepository->findProductByID($params['product_id']);
		$slug = $product->slug;

        // $attributes = $this->catalogueRepository->getAttributesByProduct($params);
        // $attributes = $this->catalogueRepository->getProductByAttributes($product, $params);

		$attributes = [];
		if ($product->configurable()) {
			// $product = Product::from('products as p')
			// 	->whereRaw(
			// 		"p.parent_id = :parent_product_id
			// 	and (select pav.text_value
			// 			from product_attribute_values pav
			// 			join attributes a on a.id = pav.attribute_id
			// 			where a.code = :size_code
			// 			and pav.product_id = p.id
			// 			limit 1
			// 		) = :size_value
			// 	and (select pav.text_value
			// 			from product_attribute_values pav
			// 			join attributes a on a.id = pav.attribute_id
			// 			where a.code = :color_code
			// 			and pav.product_id = p.id
			// 			limit 1
			// 		) = :color_value
			// 		",
			// 		[
			// 			'parent_product_id' => $product->id,
			// 			'size_code' => 'size',
			// 			'size_value' => $params['size'],
			// 			'color_code' => 'color',
			// 			'color_value' => $params['color'],
			// 		]
			// 	)->firstOrFail();

            $product = $this->catalogueRepository->getProductByAttributes($product, $params);

			$attributes['size'] = $params['size'];
			$attributes['color'] = $params['color'];
		}

		// $itemQuantity =  $this->_getItemQuantity(md5($product->id)) + $params['qty'];
		$itemQuantity =  $this->cartRepository->getItemQuantity($product->id, $params['qty']);

		// $this->_checkProductInventory($product, $itemQuantity);

        $this->catalogueRepository->checkProductInventory($product, $itemQuantity);
        // $this->_checkProductInventory($product, $itemQuantity);

		$item = [
			'id' => md5($product->id),
			'name' => $product->name,
			'price' => $product->price,
			'quantity' => $params['qty'],
			'attributes' => $attributes,
			'associatedModel' => $product,
		];

		// \Cart::add($item);
        $this->cartRepository->addItem($item);

		\Session::flash('success', 'Product '. $item['name'] .' has been added to cart');
		return redirect('/product/'. $slug);
	}

	/**
	 * Get total quantity per item in the cart
	 *
	 * @param string $itemId item ID
	 *
	 * @return int
	 */
	// private function _getItemQuantity($itemId)
	// {
	// 	$items = \Cart::getContent();
	// 	$itemQuantity = 0;
	// 	if ($items) {
	// 		foreach ($items as $item) {
	// 			if ($item->id == $itemId) {
	// 				$itemQuantity = $item->quantity;
	// 				break;
	// 			}
	// 		}
	// 	}

	// 	return $itemQuantity;
	// }

	/**
	 * Check product inventory
	 *
	 * @param Product $product      product object
	 * @param int     $itemQuantity qty
	 *
	 * @return int
	 */
	// private function _checkProductInventory($product, $itemQuantity)
	// {
	// 	if ($product->productInventory->qty < $itemQuantity) {
	// 		throw new \App\Exceptions\OutOfStockException('The product '. $product->sku .' is out of stock');
	// 	}
	// }

	/**
	 * Get cart item by card item id
	 *
	 * @param string $cartID cart ID
	 *
	 * @return array
	 */
	// private function _getCartItem($cartID)
	// {
	// 	$items = \Cart::getContent();

	// 	return $items[$cartID];
	// }

	/**
	 * Update the specified resource in storage.
	 *
	 * @param \Illuminate\Http\Request $request request form
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request)
	{
		$params = $request->except('_token');

		if ($items = $params['items']) {
			foreach ($items as $cartID => $item) {
				// $cartItem = $this->_getCartItem($cartID);
				$cartItem = $this->cartRepository->getCartItem($cartID);
				// $this->_checkProductInventory($cartItem->associatedModel, $item['quantity']);
				$this->catalogueRepository->checkProductInventory($cartItem->associatedModel, $item['quantity']);

				// \Cart::update(
				// 	$cartID,
				// 	[
				// 		'quantity' => [
				// 			'relative' => false,
				// 			'value' => $item['quantity'],
				// 		],
				// 	]
				// );

                $this->cartRepository->updateCart($cartID, $item['quantity']);
			}

			\Session::flash('success', 'The cart has been updated');
			return redirect('carts');
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param string $id card ID
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		// \Cart::remove($id);
        $this->cartRepository->removeItem($id);

		return redirect('carts');
	}
}
