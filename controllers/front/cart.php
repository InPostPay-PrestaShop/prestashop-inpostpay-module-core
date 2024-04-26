<?php

class InpostIziCartModuleFrontController extends ModuleFrontController
{
    protected $content_only;

    public function displayAjax()
    {
        $this->display();
    }

    public function display()
    {
        header('Content-type: application/json');

        $products = $this->context->cart->getProducts();
        $nbTotalProducts = 0;

        foreach ($products as $product) {
            $nbTotalProducts += (int) $product['cart_quantity'];
        }

        exit(json_encode(['count' => $nbTotalProducts]));
    }
}
