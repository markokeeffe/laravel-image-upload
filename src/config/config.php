<?php

return array(
  'handle' => 'images',
  'placeholder' => 'Penguins.jpg',
  'notFound' => 'Desert.jpg',
  'dir' => 'img/content',
  'useCDN' => false,

  'rackspace' => array(
    'endpoint' => 'https://lon.identity.api.rackspacecloud.com/v2.0/',
    'cacert' => 'cacert.pem',
    'username' => 'veneficuscloud',
    'api_key' => '8d7af1aea5f6979997ccc9955235a614',
    'container' => 'laravel_image_uploader',
    'containerUrl' => 'http://9d0728e945915fdffbd3-b33535423b63450300f72153214dfe90.r92.cf3.rackcdn.com/',
  ),

  'assets' => array(
    'css' => array(
      'v.image-upload.css',
      'jquery.Jcrop.css',
    ),
    'js' => array(
      'behaviors/*',
      'functions.js',
      'jquery.Jcrop.js',
    ),
  ),
);
