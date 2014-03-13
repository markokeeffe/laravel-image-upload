<?php namespace MOK\ImageUpload\Repositories;

interface LocalRepositoryInterface
{
  public function add($data);

  public function update($id, $data);

  public function get($id);

  public function addSize($id, $size);

  public function removeSize($id, $size);

  public function editSizes($currentSizes, $size, $action);
}
