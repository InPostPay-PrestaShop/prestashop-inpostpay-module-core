<?php

use izi\prestashop\ObjectModel\Entity\InPostIziBasketSession as BaseSessionModel;

/**
 * Global namespace alias used on PS 1.7.0.
 * Before 1.7.1 namespaced classes extending {@see \ObjectModel} dispatch hooks with invalid (i.e. containing backslashes) names.
 */
class InPostIziBasketSession extends BaseSessionModel
{
}
