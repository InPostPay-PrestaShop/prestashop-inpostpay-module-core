<?php

class InpostIziCartModuleFrontController extends ModuleFrontController
{

    public function displayAjax()
    {
        return $this->display();
    }

    public function display()
    {
        header('Content-type: application/json');
        $context = \Context::getContext();
        $id_cart = $context->cookie->id_cart;
        if ($id_cart == '') {
            $id_cart = \Tools::getValue('id_cart');
        }

        $theCart = new \Cart($id_cart);
        $products = $theCart->getProducts(true);
        $nbTotalProducts = 0;

        foreach ($products as $product) {
            $nbTotalProducts += (int)$product['cart_quantity'];
        }

        die(json_encode(['count' => $nbTotalProducts]));
    }
}