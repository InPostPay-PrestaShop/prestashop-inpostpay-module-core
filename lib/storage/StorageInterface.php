<?php

namespace izi\storage;

interface StorageInterface {
    /**
     * Save data of type for basket id
     * @param $id string
     * @param $name string
     * @param $string string
     * @return boolean
     */
    public function save($id, $type, $data);

    /**
     * Check if data of type for basket id exists
     * @param $id
     * @param $type
     * @return mixed
     */
    public function exist($id, $type);

    /**
     * Load data of type for basket id
     * @param $id
     * @param $type
     * @return string
     */
    public function load($id, $type);

    /**
     * Drop data of type for basket id
     * @param $id
     * @param $type
     * @return boolean
     */
    public function drop($id, $type);
}