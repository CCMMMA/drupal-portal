
(function($, Drupal, drupalSettings) {
  
  
  //functions      
  function get_map(type, time){
    var zoom = 8;
    if(type == 'euro' || type == 'world'){
      zoom = 4;
    }
    
    //var map = L.map('mapid-'+type).setView([40.863, 14.2767], zoom);
    //var src_map = 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpandmbXliNDBjZWd2M2x6bDk3c2ZtOTkifQ._QA7i5Mpkd_m30IGElHziw';
    //src_map = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    //var attribution = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                  '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                  'Imagery &copy; <a href="http://mapbox.com">Mapbox</a>';
    //L.tileLayer(src_map, {
    //maxZoom: 22,
    //attribution:  attribution,
    //id: 'mapbox.streets'
    //}).addTo(map);
    
    
    /* Mappa gestita con tiles */
    L.mapbox.accessToken = 'pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpandmbXliNDBjZWd2M2x6bDk3c2ZtOTkifQ._QA7i5Mpkd_m30IGElHziw';
    map = L.mapbox.map('mapid-'+type, 'mapbox.streets')
    .setView([40.863, 14.2767], zoom);
    
    
    
    var sunny_png = L.icon({
        iconUr2l: '/sites/all/themes/zircon_custom/js/images/sunny.png',
        iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/sunny.png',
        iconSize: [60, 60],
        iconAnchor: [9, 21],
        popupAnchor: [20, -17]
    });
    var cloudy1_png = L.icon({
        iconUr2l: '/sites/all/themes/zircon_custom/js/images/cloudy1.png',
        iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy1.png',
        iconSize: [60, 60],
        iconAnchor: [9, 21],
        popupAnchor: [20, -17]
    });  
    
    /*
       ......  
    */
    
    var meteo_condition = cloudy1_png;
    
    var markerClusters = L.markerClusterGroup();     
    $.getJSON("https://meteo.uniparthenope.it/sites/default/files/places_data.json",function(data){
          for ( var i = 0; i < data.length; ++i )  //Processo tutti i place ottenuti
          {
            var id_place = data[i].properties.id_place;
            var nid_place = data[i].properties.nid;
            var lat_place =  data[i].geometry.coordinates[0];
            var long_place = data[i].geometry.coordinates[1];
            if(type != 'all' && type != 'other'){
              if(id_place.indexOf(type) != -1){
                //TODO recupoero info meteo singolo place
                 /*
                 *    Creo Popup
                 */
                 var popup = data[i].properties.name +
                              '<br/><b>ID Place: </b> ' + id_place +
                              '<br/><a href="/node/'+ nid_place +'">Maggiori info </a> '+
                              '<br/><b>Condizioni meteo attuali: ' + /*condizione*/ + '</b>';
                              
                //Estendo la classe marker per inserire dati custom all'interno del marker
                customMarker = L.Marker.extend({
                  options: { 
                    id: 'Custom id',
                 }
                });
                
                var mymarker = new customMarker([long_place, lat_place],
                                         {icon: meteo_condition, 
                                          idPlace: id_place, 
                                          });
                var mymarker_popup = mymarker.bindPopup( popup );
                                          
                
                                                        
                 //Aggiungo il marker e il popup                          
                 //var m = L.marker( [long_place, lat_place], {icon: meteo_condition})
                 //               .bindPopup( popup );
                 /*
                   var m = L.marker( [long_place, lat_place], {icon: ico_sunny})
                                .bindPopup( popup );
                 */
                 // END Creo popup
                 markerClusters.addLayer( mymarker_popup );
              }
            }
            else{
    
                 /*
                 *    Creo Popup
                 */
                 var popup = data[i].properties.name +
                              '<br/><b>ID Place: </b> ' + id_place +
                              '<br/><a href="/node/'+ nid_place +'">Maggiori info </a> '+
                              '<br/><b>Condizioni meteo attuali: ' + /*condizione +*/ '</b>';                          
                 //Aggiungo il marker e il popup                          
                 var m = L.marker( [long_place, lat_place])
                                .bindPopup( popup );
                 // END Creo popup
                 markerClusters.addLayer( m );
            }                 
          }
          //Aggiongo tutti i places
          map.addLayer( markerClusters );
                    
          /* FOR TEST */
          /*
          markerClusters.on('click', function (L) {
            console.log( L.layer);
          });
          
          */
          
          markerClusters.on('clusterclick', function (p) {
            console.log('stampo markercluster:');
            console.log(p.layer);
            // a.layer is actually a cluster
            var figli = p.layer.getAllChildMarkers();
            var numero_figli = figli.length;
            console.log('stampo i figli:');
            console.log(figli);
            window.asd = new Array();
            
            $.each( figli, function( index, cluster_layer ){
              //console.log(cluster_layer);
              window.asd.push(cluster_layer);   
              console.log(cluster_layer.getChildCount())           
              /*
               if(cluster_layer.dragging.size()){
                 console.log('sono un marker');
               }
               else{
                 console.log('sono un cluster');
               }
              */
               //console.log(cluster_layer['_icon']);
               /*
               if(cluster_layer._icon.size()){
                var is_cluster = 
              */
              
               
              /*
                  markerClusters.removeLayer(cluster_layer);
                  meteo_condition = sunny_png;
                  var m = L.marker( [long_place, lat_place], {icon: meteo_condition})
                                .bindPopup( popup );
                  markerClusters.addLayer( m );
                  
              */
              //Cambio in base al tempo restituito
              cluster_layer.setIcon(sunny_png);
                               
                  //console.log(cluster.toGeoJSON()); //ho tutti i primi elementi di ogni cluster
                  /*
                  if (cluster_layer.getChildCount()){  
                    console.log('sono cluster');
                  }
                  else{
                    console.log('sono pin');
                    //console.log(cluster.getChildCount);
                  }
                  */
          });
          //console.log(window.asd[3].dragging);
        });
        
        
        
          map.on('zoomend', function () {
            console.log(map.getZoom());
          }); 
          
          /*
          map.on('zoomend', function () {
            if (map.getZoom() > 9 && map.hasLayer(heatmapLayer)) {
                map.removeLayer(heatmapLayer);
            }
            if (map.getZoom() < 9 && map.hasLayer(heatmapLayer) == false)
            {
                map.addLayer(heatmapLayer);
            }
          });
            */   
                    
          /*
          
          markerClusters.on('animationend', function (L) {
          // a.layer is actually a cluster
            console.log(markerClusters);
          });
          
          markerClusters.on('clusterclick', function (L) {
              var latLngBounds = L.layer.getBounds();
              console.log(latLngBounds);
          });
          
         /*
          map.eachLayer(function(layer){     //iterate over map rather than clusters
            if (layer.getChildCount){         // if layer is markerCluster
              //qui il layer è un cluster
              child_of_singular_cluster = layer.getAllChildMarkers();
              $.each( child_of_singular_cluster, function( index, marker ){  
                if(index == 0){
                  console.log(marker.toGeoJSON()); //ho tutti i primi elementi di ogni cluster
                }
              });
              
            }
          });
          */       
    });
  }
  
  
  
  
  
  Drupal.behaviors.yourbehavior = {
    attach: function (context, settings) {
      //get time now
      var time_now_temp = moment().format();
      var time_now = time_now_temp.replace(/-/g, '');

      //Viene eseguito di default
      get_map('com', time_now);      
      
      //Quando effettuo una scelta
      $('.scelta-singola').click(function(){
          if($(this).hasClass( 'selected' )){
          }
          else {
            $('.scelta-singola.selected').removeClass('selected');
            $(this).addClass('selected');
            //Cambio markup della mappa
            type = $(this).attr("data");
            old_type = $('.mapid').attr("id")
            $('.mapid').replaceWith('<div id="mapid-'+type+'" class="mapid"></div>');
            
            //Ristampo la mappa
            get_map(type, time_now); 
          }
      });    
    
    
    
    
    }
  };
})(jQuery, Drupal, drupalSettings);
 

  
  
/*
 *    Recupero condizioni meteo del place
 */
 /*
resturl = 'http://192.167.9.103:5050/products/wrf3/forecast/'+ id_place;
var condizione = '';
var condizione_meteo = function(data){
    condizione = data.forecast.place.text;  
};
/*
$.ajax({
  url: resturl,
  dataType: 'JSONP',
  type: 'GET',
  async: false,
  success: condizione_meteo
});
*/
//END recupero condizioni meteo del place
    

