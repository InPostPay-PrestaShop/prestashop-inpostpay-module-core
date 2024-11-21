<?php

class InpostIziCartModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        header('Content-type: application/json');
        $nbTotalProducts = 0;

        if (Validate::isLoadedObject($this->context->cart) === false) {
            exit(json_encode(['count' => $nbTotalProducts]));
        }

        $products = $this->context->cart->getProducts();

        foreach ($products as $product) {
            $nbTotalProducts += (int) $product['cart_quantity'];
        }

        exit(json_encode(['count' => $nbTotalProducts]));
    }
}
