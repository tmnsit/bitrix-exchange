<?php
namespace Prioritet\Exchange;

use ReflectionClass;
use ReflectionProperty;

abstract class BaseEntity{
    public function fromArray(array $dataProduct){
        $entity = $this;

        $reflect = new ReflectionClass($entity);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop){
            $reflect->getProperty($prop->name)->setValue($entity, $dataProduct[$prop->name]);
        }
        return $entity;
    }
}