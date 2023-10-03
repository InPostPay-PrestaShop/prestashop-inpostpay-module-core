<?php

class InpostIziFrontendModuleFrontController extends ModuleFrontController
{
    public $ajax;

    public function displayAjax()
    {
        return $this->display();
    }

    public function display()
    {
        echo '<center><h2>Dziękujemy za zakupy!</h2></center>';
    }
}