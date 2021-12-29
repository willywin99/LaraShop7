<?php

namespace App\Repositories\Admin\Interfaces;

interface ProductRepositoryInterface
{
    public function paginate(int $perPage);

    public function create($params = []);

    public function findById(int $id);

    public function update($params = [], int $id);

    public function delete(int $id);

    public function addImage(int $id, $image);

    public function findImageById(int $id);

    public function removeImage(int $id);

    public function types();

    public function statuses();
}
