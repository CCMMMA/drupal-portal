<?php

namespace Drupal\home;

class BollettinoServices {
  protected $fields;
  protected $products;

  public function SetData($product, $only_key = FALSE) {
    $this->SetAllProducts($only_key);
    $this->SetAllFields($product, $only_key);
  }

  public function GetAllFields(){
    return $this->fields;
  }

  public function GetAllProducts(){
    return $this->products;
  }

  public function SetAllProducts($only_key = FALSE){
    $api = \Drupal::config('api.settings')->get('api');
    $url = $api . '/products';

    $client = new \GuzzleHttp\Client();
    try {
      $request = $client->get($url, ['http_errors' => FALSE]);
      $response = json_decode($request->getBody());

      if (isset($response->products)) {
        if($only_key){
          foreach($response->products as $key => $val){
            $products[$key] = $key;
          }
        } else {
          $products = $response->products;
        }
      }
    } catch (RequestException $e) {
      $products = [];
    }
    $this->products = $products;
  }

  public function SetAllFields($product, $only_key = FALSE){
    $api = \Drupal::config('api.settings')->get('api');
    $url = $api . '/products/'.$product.'/fields';

    $fields = [];

    $client = new \GuzzleHttp\Client();
    try {
      $request = $client->get($url, ['http_errors' => FALSE]);
      $response = json_decode($request->getBody());

      if (isset($response->fields)) {
        if($only_key){
          foreach($response->fields as $key => $val){
            $fields[$key] = $key;
          }
        } else {
          $fields = $response->fields;
        }
      }
    } catch (RequestException $e) {}
    $this->fields = $fields;
  }
}