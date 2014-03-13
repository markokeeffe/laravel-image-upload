<?php namespace MOK\ImageUpload\Repositories;

interface RemoteRepositoryInterface
{
  public function listObjects($prefix=null);

  public function hasObject($name);

  public function getObject($name);

  public function saveObject($name, $data);

  public function deleteObject($name);

  public function purgeObject($object);

  public function getContainer($name);

  public function getUrl($name);
}
