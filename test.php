<?php
date_default_timezone_set("UTC");
$date="20181123Z1100UTC";
      $date_strtotime = strtotime($date);
      $utc = date("H",$date_strtotime);
echo "$date<br>\n";
echo "UTC: $utc<br>\n";
$current_minutes = 0; //date('i', $date_strtotime);
    $date_used = date("Y-m-d", $date_strtotime); //Y-m-d
echo $date_used ."<br>\n";
    $date_form = $date_used;  //da utilizzare nel form
    $utc_list = range(0, 23);
    $form['utc'] = array(
      '#type' => 'select',
      '#title' => 'UTC (CET=UTC+1)',
      '#options' => $utc_list,
      '#default_value' => (int)$utc,
    );
    $ldate = strtotime($date_form) + $utc*3600;
    $pdate = date("Ymd\ZHi",$ldate-3600);
    $ldate = date("Ymd\ZHi",$ldate+3600);
    $base_url = '/forecast/forecast?product='.$prod.'&place='.$id_place.'&mappa='.$mappa.'&output='.$output.'&date=';
echo $form['utc']['#default_value']."<br>\n";
echo $base_url.$pdate."<br>\n";
echo $base_url.$ldate."<br>\n";
phpinfo();
?>
