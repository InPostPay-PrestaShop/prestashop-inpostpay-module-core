<?php

use Symfony\Component\Config\Resource\ReflectionClassResource;

// static properties containing objects could cause memory leaks during signature generation (before version 3.4.31 of symfony/config)
if (!class_exists(ReflectionClassResource::class, false) && defined('_PS_VERSION_') && 0 === strpos(_PS_VERSION_, '1.7.6')) {
    require_once __DIR__ . '/vendor/symfony/config/Resource/ReflectionClassResource.php';
}
