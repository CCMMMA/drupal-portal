<?php 
  function get_prod($id, $product, $count, &$context){
    /*
    $id_prod = $id;
    if(isset($product['name'])){
      $name_prod = $product['name'];
    }
    else{
      $name_prod = '';
    }
    if(isset($product['desc'])){
      $desc_prod = $product['desc'];
    }
    else{
      $desc_prod = '';
    }
    
    $map_output = array();
    if(isset($product['map_output'])){
      foreach($product['map_output'] as $output_use){
        $map_output[] = array("target_id" => $output_use);
      }
    }
    $map_output_nid = get_nids_of_terms_by_name($map_output, 'outputs');
    $chart_output = array();
    if(isset($product['chart_output'])){
      foreach($product['chart_output'] as $output_use){
        $chart_output[] = array("target_id" => $output_use);
      } 
    } 
    $chart_output_nid = get_nids_of_terms_by_name($chart_output, 'outputs');
    //CERCO SE ESISTE IL PRODUCT
    $query = \Drupal::entityQuery('node')
      ->condition('field_id_products', $id_prod, '=');
    $nids = $query->execute();
    if(empty($nids)){  //il place non è presente
      //dpm('Creo il nuovo product');
      $product = entity_create('node', array(
        'type' => 'products',
        'title' => $id_prod,
        'field_id_products' => $id_prod,
        'field_name_products' => $name_prod,
        'field_description_products' => $desc_prod,
        'field_chart_output_products' => $chart_output_nid,
        'field_map_output_products' => $map_output_nid,
      ));
      $product->save();
    }
    else{ //il prod è già presente
      $prod_id = array_shift($nids);
      //dpm('Aggiorno il prod esistente con nid = '.$prod_id);
      $product = \Drupal::entityManager()->getStorage('node')->load($prod_id);
      $product->set('title', $id_prod);
      $product->set('field_id_products', $id_prod);
      $product->set('field_name_products', $name_prod);
      $product->set('field_description_products', $desc_prod);
      //Add chart e map output
      $product->set('field_chart_output_products', $chart_output_nid);
      $product->set('field_map_output_products', $map_output_nid);
      //dpm($product->toArray());
      $product->save();
    }
    $context['message'] = 'Sto processando questo prods: '.$name_prod; 
    */
    $context['results']['prods'][] = $name_prod;
  }
  
  function get_output($id, $output, $count, &$context){
    /*
    $id_output = $id;
    $name_output = $output['name'];
    $desc_output = $output['desc'];
    //CERCO SE ESISTE L'OUTPUT
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "outputs");
    $query->condition('field_name', $id_output);
    $tids = $query->execute();
    if(empty($tids)){  //il place non è presente
      //dpm('Creo il nuovo output');
      $term = \Drupal\taxonomy\Entity\Term::create([
        'vid' => 'outputs',
        'name' => [$id_output],
        'field_name' => [$id_output],
        'field_outputs_id' => [$id_output],
        'field_outputs_description' => [$desc_output],
      ]);
      $term->save();
      //dpm('Term creato');
    }
    else{ //l'output è già presente
      /*
      $term_id = array_shift($tids);
      //dpm("Aggiorno l'output esistente con nid = ".$term_id);
      //$term = \Drupal\taxonomy\Entity\Term::load($term_id);
      $term = \Drupal::entityManager()->getStorage('taxonomy_term')->load($term_id);
      //dpm($term);
      $term->set('field_outputs_description', $desc_output);
      $term->set('field_outputs_id', $id_output);
      $term->set('field_name', $name_output);
      $term->save();    
      */                             //     ??? TODO Da verificare il funzionamento
    //} 
    /*
    $context['message'] = 'Sto processando questo outputs: '.$name_output; 
    */
    $context['results']['outputs'][] = $name_output;
  }
  
  function get_place($id, $element, $count, &$context){
    
    $id_place = $id;
    $cLat = $element['cLat'];  // <------   coordinate
    $cLon = $element['cLon'];  // <------   coordinate
    $diag = $element['diag'];
    $long_name = $element['long_name']['it'];
    $maxLat = $element['maxLat'];
    $maxLon = $element['maxLon'];
    $minLat = $element['minLat'];
    $minLon = $element['minLon'];
    $name_place = $element['name']['it'];
    //get domains use
    $domain = array();
    if(isset($element['domains'])){
      foreach($element['domains'] as $domains_use){
        $domain[] = array("target_id" => $domains_use);
      }
    }
    //dpm($domain);
    $domains_id = get_nids_of_terms_by_name($domain, 'domains');
    //dpm($domains_id);
    //end domain use
    //get prods use
    $prods = array();
    if(isset($element['prods'])){
      foreach($element['prods'] as $prods_use){
        $prods[] = array("target_id" => $prods_use);
      }
    }
    //dpm($prods);
    $prods_id = get_nids_of_prods_by_name_prods($prods, '');
    //dpm($prods_id);
    //end prod use
    //CERCO SE ESISTE IL PLACE
    $query = \Drupal::entityQuery('node')
      ->condition('field_id_place', $id_place, '=');
    $nids = $query->execute();
    
    //il place non è presente  
    if(empty($nids)){  
      //dpm('Creo il nuovo place');
      //Genero places
      $test = array(
        'value' => 'POINT ('.$cLon.' '.$cLat.')',
        'geo_type' => 'Point',
                'lat' => $cLat,
                'lon' => $cLon,
                'left' => $cLon,
                'top' => $cLat,
                'right' => $cLon,
                'bottom' => $cLat,
                //'geohash' => 'u0nd9he8e4',
                'latlon' => $cLat.','.$cLon,
      );
      
      $product = entity_create('node', array(
        'type' => 'place',
        'title' => $name_place . ' - ' . $id_place,
        'field_id_place' => $id_place,
        'field_diag' => $diag,
        'field_long_name' => $long_name,
        'field_max_lat' => $maxLat,
        'field_max_lon' => $maxLon,
        'field_min_lat' => $minLat,
        'field_min_lon' => $minLon,
        'field_name_place' => $name_place,
        'field_domains_place' => $domains_id,
        'field_products' => $prods_id,
        'field_coordinate_place' => $test,
      ));
      $product->save();
  
    }
    else{ //il place è già presente
      //AGGIORNO SOLO IL TIPO DI PLACE
      
      $place_id = array_shift($nids);
      $place = \Drupal::entityManager()->getStorage('node')->load($place_id);
      
      //Verifico se è già stato inserito il field
      $type = $place->get('field_place_type_of_place')->getValue();
      if(!isset($type) && empty($type)){
        $id_arr = $place->get('field_id_place')->getValue();
        $id = $id_arr[0]['value'];
        //dpm($id); 
        $first_five_char = substr($id, 0, 5);
        $first_five_char_lower = strtolower($first_five_char);
        //todo get all taxonomy term type of place
        $all_type_of_place = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('type_of_place');
        foreach($all_type_of_place as $type_of_place){
          $type = $type_of_place->name;
          $type_lower = strtolower($type);
          $pos = strpos($first_five_char_lower, $type_lower);
          if ($pos === false) {
              continue;
          } else {
            $tid_place = $type_of_place->tid;
            $place->set('field_place_type_of_place', $tid_place);
          }
        }
        $place->save();
      }
    }
    //$context['results']['places'][] = $name_place .' - '. $id_place ;
    $context['message'] = 'Sto processando questo Places: '.$name_place .' - '. $id_place; 
      
    $context['results']['places']++;
  }
  
  //TODO lista di dati aggiornati e creati (da fare)
  
  function finished_importer_data_callback_prods_and_outputs($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One data processed.', '@count data processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
    dpm('finito primo batch');
    // Providing data for the redirected page is done through $_SESSION.
    /*
    foreach ($results as $result) {
      $items[] = t('Loaded node %title.', array('%title' => $result));
    }
    */
    //$_SESSION['my_batch_results'] = $items;
  }
  
  function finished_importer_data_callback_places($success, $results, $operations){
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One data processed.', '@count data processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
    dpm('finito secondo batch');
    dpm($results);
  }

  function get_nids_of_terms_by_name($array_of_term, $type){
    switch ($type) {
      case 'outputs':
        $result = array();
        foreach($array_of_term as $term){
          $query = \Drupal::entityQuery('taxonomy_term')
                ->condition('field_name', $term['target_id'], '=');
              $nid = $query->execute();
          $result[] = array('target_id' => array_shift($nid));
        }
        break;
      case 'domains':
        $result = array();
          foreach($array_of_term as $term){
            $query = \Drupal::entityQuery('taxonomy_term')
                  ->condition('field_id', $term['target_id'], '=');
                $nid = $query->execute();
            $result[] = array('target_id' => array_shift($nid));
          }
        break;
  
    }
    return $result;
  }  
  
  function get_nids_of_prods_by_name_prods($array_of_prods){
    $result = array();
    foreach($array_of_prods as $term){
      $query = \Drupal::entityQuery('node')
            ->condition('field_id_products', $term['target_id'], '=');
          $nid = $query->execute();
      $result[] = array('target_id' => array_shift($nid));
    }
    return $result;
  }
