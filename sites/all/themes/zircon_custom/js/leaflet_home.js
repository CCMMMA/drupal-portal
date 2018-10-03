var api_url_base = "http://193.205.230.6";

(function ($, Drupal, drupalSettings) {
  //functions
  function insert_button() {
    if ($('div.inc').length == 0) {
      $("div.select-hour").append('<div class="inc button">></div><div class="dec button"><</div>');
    }
  }

  // Load data tiles from an AJAX data source
  L.TileLayer.Ajax = L.TileLayer.extend({
    _requests: [],
    _addTile: function (tilePoint) {
      var tile = {datum: null, processed: false};
      this._tiles[tilePoint.x + ':' + tilePoint.y] = tile;
      this._loadTile(tile, tilePoint);
    },
    // XMLHttpRequest handler; closure over the XHR object, the layer, and the
    // tile
    _xhrHandler: function (req, layer, tile, tilePoint) {
      return function () {
        if (req.readyState !== 4) {
          return;
        }
        var s = req.status;
        if ((s >= 200 && s < 300 && s != 204) || s === 304) {
          tile.datum = JSON.parse(req.responseText);
          layer._tileLoaded(tile, tilePoint);
        }
        else {
          layer._tileLoaded(tile, tilePoint);
        }
      };
    },
    // Load the requested tile via AJAX
    _loadTile: function (tile, tilePoint) {
      this._adjustTilePoint(tilePoint);
      var layer = this;
      var req = new XMLHttpRequest();
      this._requests.push(req);
      req.onreadystatechange = this._xhrHandler(req, layer, tile, tilePoint);
      req.open('GET', this.getTileUrl(tilePoint), true);
      req.send();
    },
    _reset: function () {
      L.TileLayer.prototype._reset.apply(this, arguments);
      for (var i = 0; i < this._requests.length; i++) {
        this._requests[i].abort();
      }
      this._requests = [];
    },
    _update: function () {
      if (this._map && this._map._panTransition && this._map._panTransition._inProgress) {
        return;
      }
      if (this._tilesToLoad < 0) {
        this._tilesToLoad = 0;
      }
      L.TileLayer.prototype._update.apply(this, arguments);
    }
  });

  L.TileLayer.GeoJSON = L.TileLayer.Ajax.extend({
    // Store each GeometryCollection's layer by key, if options.unique function
    // is present
    _keyLayers: {},

    // Used to calculate svg path string for clip path elements
    _clipPathRectangles: {},

    initialize: function (url, options, geojsonOptions) {
      L.TileLayer.Ajax.prototype.initialize.call(this, url, options);
      this.geojsonLayer = new L.GeoJSON(null, geojsonOptions);
    },
    onAdd: function (map) {
      this._map = map;
      L.TileLayer.Ajax.prototype.onAdd.call(this, map);
      map.addLayer(this.geojsonLayer);
    },
    onRemove: function (map) {
      map.removeLayer(this.geojsonLayer);
      L.TileLayer.Ajax.prototype.onRemove.call(this, map);
    },
    _reset: function () {
      this.geojsonLayer.clearLayers();
      this._keyLayers = {};
      this._removeOldClipPaths();
      L.TileLayer.Ajax.prototype._reset.apply(this, arguments);
    },

    _getUniqueId: function () {
      return String(this._leaflet_id || ''); // jshint ignore:line
    },

    // Remove clip path elements from other earlier zoom levels
    _removeOldClipPaths: function () {
      for (var clipPathId in this._clipPathRectangles) {
        var prefix = clipPathId.split('tileClipPath')[0];
        if (this._getUniqueId() === prefix) {
          var clipPathZXY = clipPathId.split('_').slice(1);
          var zoom = parseInt(clipPathZXY[0], 10);
          if (zoom !== this._map.getZoom()) {
            var rectangle = this._clipPathRectangles[clipPathId];
            this._map.removeLayer(rectangle);
            var clipPath = document.getElementById(clipPathId);
            if (clipPath !== null) {
              clipPath.parentNode.removeChild(clipPath);
            }
            delete this._clipPathRectangles[clipPathId];
          }
        }
      }
    },

    // Recurse LayerGroups and call func() on L.Path layer instances
    _recurseLayerUntilPath: function (func, layer) {
      if (layer instanceof L.Path) {
        func(layer);
      }
      else if (layer instanceof L.LayerGroup) {
        // Recurse each child layer
        layer.getLayers().forEach(this._recurseLayerUntilPath.bind(this, func), this);
      }
    },

    _clipLayerToTileBoundary: function (layer, tilePoint) {
      // Only perform SVG clipping if the browser is using SVG
      if (!L.Path.SVG) {
        return;
      }
      if (!this._map) {
        return;
      }

      if (!this._map._pathRoot) {
        this._map._pathRoot = L.Path.prototype._createElement('svg');
        this._map._panes.overlayPane.appendChild(this._map._pathRoot);
      }
      var svg = this._map._pathRoot;

      // create the defs container if it doesn't exist
      var defs = null;
      if (svg.getElementsByTagName('defs').length === 0) {
        defs = document.createElementNS(L.Path.SVG_NS, 'defs');
        svg.insertBefore(defs, svg.firstChild);
      }
      else {
        defs = svg.getElementsByTagName('defs')[0];
      }

      // Create the clipPath for the tile if it doesn't exist
      var clipPathId = this._getUniqueId() + 'tileClipPath_' + tilePoint.z + '_' + tilePoint.x + '_' + tilePoint.y;
      var clipPath = document.getElementById(clipPathId);
      if (clipPath === null) {
        clipPath = document.createElementNS(L.Path.SVG_NS, 'clipPath');
        clipPath.id = clipPathId;

        // Create a hidden L.Rectangle to represent the tile's area
        var tileSize = this.options.tileSize,
            nwPoint = tilePoint.multiplyBy(tileSize),
            sePoint = nwPoint.add([tileSize, tileSize]),
            nw = this._map.unproject(nwPoint),
            se = this._map.unproject(sePoint);
        this._clipPathRectangles[clipPathId] = new L.Rectangle(new L.LatLngBounds([nw, se]), {
          opacity: 0,
          fillOpacity: 0,
          clickable: false,
          noClip: true
        });
        this._map.addLayer(this._clipPathRectangles[clipPathId]);

        // Add a clip path element to the SVG defs element
        // With a path element that has the hidden rectangle's SVG path string
        var path = document.createElementNS(L.Path.SVG_NS, 'path');
        var pathString = this._clipPathRectangles[clipPathId].getPathString();
        path.setAttribute('d', pathString);
        clipPath.appendChild(path);
        defs.appendChild(clipPath);
      }

      // Add the clip-path attribute to reference the id of the tile clipPath
      this._recurseLayerUntilPath(function (pathLayer) {
        pathLayer._container.setAttribute('clip-path', 'url(#' + clipPathId + ')');
      }, layer);
    },

    // Add a geojson object from a tile to the GeoJSON layer
    // * If the options.unique function is specified, merge geometries into
    // GeometryCollections grouped by the key returned by
    // options.unique(feature) for each GeoJSON feature * If options.clipTiles
    // is set, and the browser is using SVG, perform SVG clipping on each
    // tile's GeometryCollection
    addTileData: function (geojson, tilePoint) {
      var features = L.Util.isArray(geojson) ? geojson : geojson.features,
          i, len, feature;

      if (features) {
        for (i = 0, len = features.length; i < len; i++) {
          // Only add this if geometry or geometries are set and not null
          feature = features[i];
          if (feature.geometries || feature.geometry || feature.features || feature.coordinates) {
            this.addTileData(features[i], tilePoint);
          }
        }
        return this;
      }

      var options = this.geojsonLayer.options;

      if (options.filter && !options.filter(geojson)) {
        return;
      }

      var parentLayer = this.geojsonLayer;
      var incomingLayer = null;
      if (this.options.unique && typeof(this.options.unique) === 'function') {
        var key = this.options.unique(geojson);

        // When creating the layer for a unique key,
        // Force the geojson to be a geometry collection
        if (!(key in this._keyLayers && geojson.geometry.type !== 'GeometryCollection')) {
          geojson.geometry = {
            type: 'GeometryCollection',
            geometries: [geojson.geometry]
          };
        }

        // Transform the geojson into a new Layer
        try {
          incomingLayer = L.GeoJSON.geometryToLayer(geojson, options.pointToLayer, options.coordsToLatLng);
        }
            // Ignore GeoJSON objects that could not be parsed
        catch (e) {
          return this;
        }

        incomingLayer.feature = L.GeoJSON.asFeature(geojson);
        // Add the incoming Layer to existing key's GeometryCollection
        if (key in this._keyLayers) {
          parentLayer = this._keyLayers[key];
          parentLayer.feature.geometry.geometries.push(geojson.geometry);
        }
        // Convert the incoming GeoJSON feature into a new GeometryCollection
        // layer
        else {
          this._keyLayers[key] = incomingLayer;
        }
      }
      // Add the incoming geojson feature to the L.GeoJSON Layer
      else {
        // Transform the geojson into a new layer
        try {
          incomingLayer = L.GeoJSON.geometryToLayer(geojson, options.pointToLayer, options.coordsToLatLng);
        }
            // Ignore GeoJSON objects that could not be parsed
        catch (e) {
          return this;
        }
        incomingLayer.feature = L.GeoJSON.asFeature(geojson);
      }
      incomingLayer.defaultOptions = incomingLayer.options;

      this.geojsonLayer.resetStyle(incomingLayer);

      if (options.onEachFeature) {
        options.onEachFeature(geojson, incomingLayer);
      }
      parentLayer.addLayer(incomingLayer);

      // If options.clipTiles is set and the browser is using SVG
      // then clip the layer using SVG clipping
      if (this.options.clipTiles) {
        this._clipLayerToTileBoundary(incomingLayer, tilePoint);
      }
      return this;
    },

    _tileLoaded: function (tile, tilePoint) {
      L.TileLayer.Ajax.prototype._tileLoaded.apply(this, arguments);
      if (tile.datum === null) {
        return null;
      }
      this.addTileData(tile.datum, tilePoint);
    }
  });

  var sizeIco = [50, 50];

  //Inizializzazione icone meteo
  var sunny_night_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/sunny_night.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/sunny_night.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var shower1_night = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/shower1_night.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/shower1_night.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var cloudy2_night_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/cloudy2_night.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy2_night.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var shower2_night_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/shower2_night.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/shower2_night.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var cloudy1_night_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/cloudy1_night.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy1_night.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var sunny_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/sunny.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/sunny.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var cloudy1_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/cloudy1.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy1.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var cloudy2_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/cloudy3.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy3.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var cloudy3_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/cloudy3.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy3.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var cloudy4_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/cloudy4.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy4.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var cloudy5_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/cloudy5.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/cloudy5.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var shower1_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/shower1.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/shower1.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var shower2_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/shower2.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/shower2.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });
  var shower3_png = L.icon({
    iconUrl: '/sites/all/themes/zircon_custom/js/images/shower3.png',
    iconRetinaUrl: '/sites/all/themes/zircon_custom/js/images/shower3.png',
    iconSize: sizeIco,
    iconAnchor: [9, 21],
    popupAnchor: [20, -17]
  });

  var img_array = {
    'sunny_night.png': sunny_night_png,
    'shower1_night.png': shower1_night,
    'cloudy1_night.png': cloudy1_night_png,
    'cloudy2_night.png': cloudy2_night_png,
    'shower2_night.png': shower2_night_png,
    'sunny.png': sunny_png,
    'cloudy1.png': cloudy1_png,
    'cloudy2.png': cloudy2_png,
    'cloudy3.png': cloudy3_png,
    'cloudy4.png': cloudy4_png,
    'cloudy5.png': cloudy3_png,
    'shower1.png': shower1_png,
    'shower2.png': shower1_png,
    'shower3.png': shower1_png,

  };

  //Funzione per la creazione della mappa leaflet
  function get_map(type, data, ora) {
    //TODO gestire in base alle api
    var zoom = 8;
    if (type == 'euro' || type == 'world') {
      zoom = 4;
    }

    data = data.substring(0, 9) + ora;

    if (type == 'porti') {
      var geojsonURL = api_url_base + '/apps/owm/weather/harbours/{z}/{x}/{y}.geojson?date=' + data;
      zoom = 10;
    }
    else {
      var geojsonURL = api_url_base + '/apps/owm/wrf5/com/{z}/{x}/{y}.geojson?date=' + data;
    }

    /*
            // json api comuni
            var geojsonURLcomuni = 'http://192.167.9.103:5050/apps/owm/wrf3/com/{z}/{x}/{y}.geojson?date=' + data;
            // json api porti
            var geojsonURLporti = 'http://192.167.9.103:5050/apps/owm/weather/harbours/{z}/{x}/{y}.geojson?date=' + data;
    */

    //Inizializzo la mappa
    var map = new L.Map('mapid-' + type);
    //Setto la visualizzazione di defautl a Napoli
    map.setView(new L.LatLng(40.85, 14.28), zoom);
    //URL delle api
    //url_api =
    // 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpandmbXliNDBjZWd2M2x6bDk3c2ZtOTkifQ._QA7i5Mpkd_m30IGElHziw';
    url_api = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

    //Creo il layer di default
    option_layerInstance = {
      maxZoom: 18,
      attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
      'Imagery © <a href="http://mapbox.com">Mapbox</a> | MeteoUniparthenope.it',
      id: 'mapbox.streets'
    };
    var layerInstance = L.tileLayer(url_api, option_layerInstance);

    //Aggiungo il layer alla mappa
    layerInstance.addTo(map);

    // Creo lo syle per i marker
    var style = {
      "clickable": true,
      "color": "#00D",
      "fillColor": "#00D",
      "weight": 1.0,
      "opacity": 0.3,
      "fillOpacity": 0.2
    };

    // Creo il layer Json
    // Opzioni per il layer json
    option_geojsonTileLayer = {
      clipTiles: true,
    };

    // opzioni del json per il layer json
    geojsonOptions_geojsonTileLayer = {
      style: style,
      pointToLayer: function (features, latlng) {
        var file = features.properties.icon;
        //console.log(file);
        return L.marker(latlng, {icon: img_array[file]});
      },
      filter: function (feature, layer) {
        var index = feature.properties.id.search(/[0-9]/);
        var get_type = feature.properties.id.substring(0, index);
        return get_type == type;
      },
      onEachFeature: function (feature, layer) {
        if (feature.properties) {
          //console.log(feature.properties);
          country = feature.properties.country;
          city = feature.properties.name;
          id = feature.properties.id;
          clouds = parseInt(feature.properties.clf * 100); //clouds
          dateTime = feature.properties.dateTime;
          humidity = feature.properties.rh2; //umidity
          pressure = feature.properties.slp; //pressure
          temp = feature.properties.t2c; //temp
          text = feature.properties.text;
          wind_direction = feature.properties.wd10; // wind_deg
          wind_speed = feature.properties.ws10n; //wind_speed
          wind_chill = feature.properties.wchill; //wind_chill
          winds = feature.properties.winds; //winds



          popupString = "<div class='popup'>" +
              "<table class='tg' style='undefined;table-layout: fixed; width: 230px'>" +
              "<colgroup>" +
              "<col style='width: 85px'>" +
              "<col style='width: 60px'>" +
              "</colgroup>" +
              "<tr>" +
              "<th class='tg-baqh' colspan='2'><a href='/place/" + id + "'>" + city + "</a></th>" +
              "</tr>" +
              "<tr>" +
              "<td class='tg-7un6'>COUNTRY</td>" +
              "<td class='tg-7un6'>" + country + "</td>" +
              "</tr>";

/*
          $.each(fields, function (index, field) {
            console.log(feature.properties[field]);
            popupString + "<td>+ field.title.it +</td>" + "<td'>" + feature.properties[field] + " " + field.unit + "</td>";
          })
*/
          //creazione popup place

          popupString +=
              "<tr>" +
              "<td class='tg-j0tj'>TEMP</td>" +
              "<td class='tg-j0tj'>" + temp + "°C</td>" +
              "</tr>" +
              "<tr>" +
              "<td class='tg-7un6'>METEO</td>" +
              "<td class='tg-7un6'>" + text + "</td>" +
              "</tr>" +
              "<tr>" +
              "<td class='tg-j0tj'>CLOUDS</td>" +
              "<td class='tg-j0tj'>" + clouds  + "%</td>" +
              "</tr>" +
              "<tr>" +
              "<td class='tg-7un6'>HUMIDITY</td>" +
              "<td class='tg-7un6'>" + humidity + "%</td>" +
              "</tr>" +
              "<tr>" +
              "<td class='tg-j0tj'>PRESSURE</td>" +
              "<td class='tg-j0tj'>" + pressure + " HPa</td>" +
              "</tr>" +
              "<tr>" +
              "<td class='tg-7un6'>WIND DIRECTION</td>" +
              "<td class='tg-7un6'>" + wind_direction + " °N</td>" +
              "</tr>" +
              "<tr>" +
              "<td class='tg-j0tj'>WIND SPEED</td>" +
              "<td class='tg-j0tj'>" + wind_speed + " knt</td>" +
              "</tr>" +
              "<td class='tg-7un6'>WIND CHILL</td>" +
              "<td class='tg-7un6'>" + wind_chill + " *C</td>" +
              "</tr>" +
              "<td class='tg-j0tj'>WIND</td>" +
              "<td class='tg-j0tj'>" + winds + "</td>" +
              "</tr>" +
              "</table>" +
              "</div>";

          popupString += "</table>" + "</div>";

          layer.bindPopup(popupString);
        }
      }
    };
    /*
            //creo il layer json per i comuni
            var geojsonTileLayerComuni = new L.TileLayer.GeoJSON(geojsonURLcomuni, option_geojsonTileLayer, geojsonOptions_geojsonTileLayer);
     */
    //creo il layer json per i porti
    var geojsonTileLayer = new L.TileLayer.GeoJSON(geojsonURL, option_geojsonTileLayer, geojsonOptions_geojsonTileLayer);
    /*
    // Creo oggetto di layer disponibili
    var layers = {
        'Comuni': geojsonTileLayerComuni,
        'Porti': geojsonTileLayerPorti,
    };
*/

    //Aggiungo il layer alla mappa
    map.addLayer(geojsonTileLayer);
    //Aggiungo il layer alla mappa
    //map.addLayer(geojsonTileLayerPorti);

    //add loading
    var loadingControl = L.Control.loading({
      spinjs: true
    });
    map.addControl(loadingControl);


    // Add the geojson layer to the layercontrol
    // var controlLayers = L.control.layers({},layers).addTo(map);
    // Aggiungo la check per i comuni
    //controlLayers.addOverlay(geojsonTileLayerComuni, 'Comuni');
    // Aggiungo la check per i porti
    //controlLayers.addOverlay(geojsonTileLayerPorti, 'Porti');


    // Evento sulla modifica dello zoom della mappa
    map.on('zoomend', function () {
      //console.log(map.getZoom());
    });


  }

  Drupal.behaviors.behaviors_leaflet = {
    attach: function (context, settings) {

      insert_button();
      //Viene eseguito di default
      if ($('.mapid').length) {

        //get all fields for product
        url_call = api_url_base+'/products/wrf5/fields';
        $.ajax({
          url: url_call,
        }).done(function (data) {
          fields = data.fields;

          //data e ora corrente
          date = $('.scelta-singola.selected').attr("data");
          ora = $('.select-hour input').val();
          //todo gestire in base alle api
          get_map('com', date, ora);
        });



        //Quando effettuo una scelta
        $('select.selectpicker').change(function () {
          type_selected = $("option:selected", this).text().toLowerCase();
          //console.log(type_selected);
          //Cambio markup della mappa
          $('.mapid').replaceWith('<div id="mapid-' + type_selected + '" class="mapid"></div>');
          //Ristampo la mappa
          get_map(type_selected, data, ora);

        });

        $('.scelta-singola').click(function () {
          if ($(this).hasClass('selected')) {
          }
          else {
            $('.scelta-singola.selected').removeClass('selected');
            $(this).addClass('selected');
            //Cambio markup della mappa
            data = $(this).attr("data");
            type_map = $("select.selectpicker option:selected").text().toLowerCase();
            ora = $('.select-hour input').val();
            $('.mapid').replaceWith('<div id="mapid-' + type_map + '" class="mapid"></div>');
            //Ristampo la mappa
            get_map(type_map, data, ora);
          }
        });

        //add trobber for first map login
        $(document).bind("ajaxSend", function () {
          $("i.fa-spinner.fa-spin").show();
        }).bind("ajaxComplete", function () {
          $("i.fa-spinner.fa-spin").hide();
        });


        $(".select-hour .button").click(function () {
          var $button = $(this);
          var oldValue = $button.parent().find("input").val();

          if ($button.text() == ">") {
            if (parseFloat(oldValue) < 23) {
              var newVal = parseFloat(oldValue) + 1;
            }
            else {
              var newVal = parseFloat(oldValue);

            }
          }
          else {
            // Don't allow decrementing below zero
            if (oldValue > 0) {
              var newVal = parseFloat(oldValue) - 1;
            }
            else {
              newVal = 0;
            }
          }

          $button.parent().find("input").val(newVal);
          type_map = $("select.selectpicker option:selected").text().toLowerCase();
          ora = newVal;
          data = $('.scelta-singola.selected').attr("data");
          //console.log(ora);
          $('.mapid').replaceWith('<div id="mapid-' + type_map + '" class="mapid"></div>');
          //Ristampo la mappa
          get_map(type_map, data, ora);
        });

      }
    }
  };
})(jQuery, Drupal, drupalSettings);




    
    

