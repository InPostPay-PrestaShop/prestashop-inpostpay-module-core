<?php

namespace izi;

class Item
{
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            $this->throwNonExistent($property);
        }
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            $this->throwNonExistent($property);
        }
    }

    public function toArray()
    {
        $vars = get_object_vars($this);

        foreach ($vars as $key => $value) {
            if ($value instanceof Item) {
                $vars[$key] = $value->toArray();
            }

            if (is_array($value)) {
                foreach ($value as $smallKey => $smallValue) {
                    if ($smallValue instanceof Item) {
                        $vars[$key][$smallKey] = $smallValue->toArray();
                    }
                }
            }
        }

        return $vars;
    }

    public function encode()
    {
        $data = $this->toArray();
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getProducts()
    {
      return $this->toArray( )["products"];
    }

    public function compareProduct( $product )
    {
      return json_encode( $this->getProducts( ) ) === json_encode( $product );
    }

    /**
     * @param $property
     * @throws \ErrorException
     */
    protected function throwNonExistent($property): void
    {
        $class = get_class($this);
        throw new \ErrorException("Property not existing {$property} in {$class}");
    }
}
