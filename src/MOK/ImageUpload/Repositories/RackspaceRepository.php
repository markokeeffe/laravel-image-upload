<?php namespace MOK\ImageUpload\Repositories;

use OpenCloud\Rackspace;

class RackspaceRepository implements RemoteRepositoryInterface
{

  /**
   * The Laravel Application instance
   * @var \Illuminate\Foundation\Application
   */
  protected $app;

  /**
   * Config array for this API
   * @var array
   */
  protected $conf;

  /**
   * The Rackspace API
   * @var \OpenCloud\Rackspace
   */
  protected $api;

  /**
   * The Swift Object Store
   * @var \OpenCloud\ObjectStore\Service
   */
  protected $swift;

  /**
   * The cloud files container
   * @var \OpenCloud\ObjectStore\Resource\Container
   */
  protected $container;

  /**
   * Assign the app instance and config array
   * @param $app
   */
  public function __construct($app)
  {
    $this->app = $app;
    $this->conf = $this->app['config']['ImageUpload::rackspace'];
  }

  /**
   * Get the path to a file from the app 'storage' directory
   *
   * @param $file
   *
   * @return string
   */
  public function getFromStorage($file)
  {
    return storage_path($file);
  }

  /**
   * Instantiate the Rackspace open cloud API
   *
   * @return \OpenCloud\Rackspace
   */
  public function api()
  {
    if ($this->api === null) {
      $credentials = array(
        'username' => $this->conf['username'],
        'apiKey' => $this->conf['api_key'],
      );
      $curlopts = array(
//        CURLOPT_VERBOSE => true,
        CURLOPT_CAINFO => $this->getFromStorage($this->conf['cacert']),
      );
      $this->api = new Rackspace($this->conf['endpoint'], $credentials, $curlopts);
      $this->api->SetDefaults('ObjectStore','cloudFiles','LON','publicURL');
      \OpenCloud\setDebug(false);
    }
    return $this->api;
  }

  /**
   * Retrieve the swift cloud files object store
   *
   * @return \OpenCloud\ObjectStore\Service
   */
  public function swift()
  {
    if ($this->swift === null) {
      $this->swift = $this->api()->objectStore('cloudFiles', 'LON');
    }
    return $this->swift;
  }

  /**
   * Retrieve the container as specified in the config
   * e.g. 'laravel_image_uploader'
   *
   * @return \OpenCloud\ObjectStore\Resource\Container
   */
  public function cont()
  {
    if ($this->container === null) {
      $this->container = $this->getContainer($this->conf['container']);
    }
    return $this->container;
  }

  /**
   * Return a list of objects in the container
   *
   * @param null $prefix
   *
   * @return \OpenCloud\Common\Collection
   */
  public function listObjects($prefix = null)
  {
    if ($prefix) {
      $objects = $this->cont()->ObjectList(array('prefix' => $prefix));
    } else {
      $objects = $this->cont()->ObjectList();
    }

    $list = array();
    while($object = $objects->Next()) {
      $list[] = $object;
    }
    return $list;
  }

  /**
   * Check the container has an object matching the desired name
   *
   * @param $name
   *
   * @return bool
   */
  public function hasObject($name)
  {
    $search = $this->listObjects($name);
    if (is_array($search) && count($search)) {
      foreach ($search as $result) {
        if ($result->name === $name) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Get an object by its name
   *
   * @param $name
   *
   * @return bool|\OpenCloud\ObjectStore\Resource\DataObject
   */
  public function getObject($name)
  {
    try {
      $object = $this->cont()->DataObject($name);
    } catch (\Exception $e) {
      fb($e->getMessage());
      return false;
    }
    return $object;
  }

  /**
   * Save an object with a specified name
   *
   * @param $name
   * @param $data
   *
   * @return bool|\OpenCloud\ObjectStore\Resource\DataObject
   */
  public function saveObject($name, $data)
  {
    $object = $this->cont()->DataObject();

    try {
      $object->Create(array(
        'name' => $name,
        'content_type' => $data['content_type'],
      ), $data['path']);
    } catch (\Exception $e) {
      fb($e->getMessage());
      return false;
    }

    return $object;
  }

  /**
   * Delete an object by its name
   *
   * @param $name
   *
   * @return bool
   */
  public function deleteObject($name)
  {
    if ($object = $this->getObject($name)) {
      return ($object->Delete() ? true : false);
    }
    return false;
  }

  /**
   * Purge an object from the CDN
   *
   * @param $name
   *
   * @return bool
   */
  public function purgeObject($name)
  {
    if ($object = $this->getObject($name)) {
      $object->purgeCDN('mark.ok@me.com');
      return true;
    }
    return false;
  }

  /**
   * Get a cloud files container by its name, or create one if it does not exist
   *
   * @param $name
   *
   * @return mixed
   */
  public function getContainer($name)
  {
    $container = $this->swift()->Container($name);
    if (!$container) {
      $container = $this->swift()->Container();
      $container->Create($name);
    }
    return $container;
  }

  /**
   * Get the public URL for an object
   *
   * @param $name
   *
   * @return mixed
   */
  public function getUrl($name)
  {
    if ($object = $this->getObject($name)) {
      return $object->PublicURL();
    }
    return false;
  }

}
