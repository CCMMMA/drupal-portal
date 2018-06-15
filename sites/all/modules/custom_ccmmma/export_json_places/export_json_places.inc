<?php 
  
  function write_place($nid, $type, $tid_type, &$context){
    
    $query = \Drupal::entityQuery('node')
      ->condition('nid', $nid, '=');
      ->condition('field_place_type_of_place', $tid_type, '=');
    $nids = $query->execute();
    if(!empty($nids)){
      foreach($nids as $nid){
        $place = \Drupal::entityManager()->getStorage('node')->load($nid);
        $result = array();
        $title = $place->get('title')->getValue();
        $result['type'] = 'Feature';
        $geo_info = $place->get('field_coordinate_place')->getValue();
        $result['geometry'] = array(
          'type' => 'Point',
          'coordinates' => '['.$geo_info[0]['lon'].', '.$geo_info[0]['lat'].']',
        );
        $result['geometry']['coordinates'] = json_decode($result['geometry']['coordinates']);  //parse of coordinates
        $title = $place->get('field_long_name')->getValue();
        $result['properties'] = array(
          'name' => $title[0]['value'],
        );
        $id_place = $place->get('field_id_place')->getValue();
        $nid = $place->get('nid')->getValue();
        //$type_place = $place->get('field_place_type_of_place')->getValue();
        $result['properties'] = array(
          'name' => $title[0]['value'],
          'id_place' => $id_place[0]['value'],
          'nid' => $nid[0]['value'],
        );
      }
    }
    //creare o continuare file json.
    $path_public_directory = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    file_put_contents($path_public_directory.'/places_data_'.$type.'.json', json_encode($result).',', FILE_APPEND);
    
    $context['message'] = 'Sto processando questo places: '.$title[0]['value']; 
    $context['results']['place'][] = $title[0]['value'];

    
  }
  
  function finished_export_places_json_callback($success, $results, $operations){
    $path_public_directory = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    //elimino l'ultima virgola del file json
    $fh = fopen($path_public_directory.'/places_data.json', 'r+');
    $stat = fstat($fh);
    ftruncate($fh, $stat['size']-1);
    fclose($fh); 
    //chiudo le parentesi qudre dei file json
    $all_type_of_place = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('type_of_place');
    foreach($all_type_of_place as $type_of_place){
      $type = $type_of_place->name;
      $tid_type = $type_of_place->tid;
      file_put_contents($path_public_directory.'/places_data_'.$type.'.json', ']', FILE_APPEND);
    }
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results['place']), 'One data processed.', '@count data processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

