<?php

namespace ph\sen\Services;

trait EntityServiceTrait
{

    public function updateEntity($entity)
    {
        $this->objectManager->persist($entity);
        $this->objectManager->flush();
    }
}