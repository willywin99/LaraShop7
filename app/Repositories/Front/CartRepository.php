<?php

namespace App\Repositories\Front;

use App\Repositories\Front\Interfaces\CartRepositoryInterface;

class CartRepository implements CartRepositoryInterface
{
    public function getContent()
    {
        return $items = \Cart::getContent();
    }

    public function getItemQuantity($productID, $qtyRequested)
    {
        return $this->_getItemQuantity(md5($productID)) + $qtyRequested;
    }

    public function addItem($item)
    {
        return \Cart::add($item);
    }

    public function getCartItem($cartID)
    {
        $items = \Cart::getContent();

        return $items[$cartID];
    }

    public function updateCart($cartID, $qty)
    {
        return \Cart::update(
            $cartID,
            [
                'quantity' => [
                    'relative' => false,
                    'value' => $qty,
                ],
            ]
        );
    }

    public function removeItem($cartID)
    {
        return \Cart::remove($cartID);
    }

    // ----- Private -----
    private function _getItemQuantity($itemId)
	{
		$items = \Cart::getContent();
		$itemQuantity = 0;
		if ($items) {
			foreach ($items as $item) {
				if ($item->id == $itemId) {
					$itemQuantity = $item->quantity;
					break;
				}
			}
		}

		return $itemQuantity;
	}
}
