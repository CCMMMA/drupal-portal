<?php
  
namespace Drupal\radar_manage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExampleController extends ControllerBase
{
    public function autocomplete(request $request) {
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $matches = array();
      $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('title', '%'.db_like($string).'%', 'LIKE');
      //->condition('field_tags.entity.name', 'node_access');
      $nids = $query->execute();
      $result = entity_load_multiple('node', $nids);
      foreach ($result as $row) {
        //$matches[$row->nid->value] = $row->title->value;
        $matches[] = ['value' => $row->nid->value, 'label' => $row->title->value];
      }
    }
    return new JsonResponse($matches);
  }
}
  
