
var api_url_base = "http://193.205.230.6";

(function ($, Drupal, drupalSettings) {
      //functions
      function get_form_values() {
        parameters = [];
        place = $('input[data-drupal-selector="edit-place"]').val();
        //place = $('#edit-place').val();
        place = place.substring(
            place.lastIndexOf("- ") + 1,
            place.lastIndexOf("(")
        );
        parameters['place'] = place.replace(/\s/g, "");

        parameters['product'] = $('select[name=product]').val();
        parameters['output'] = $('select[name=output]').val();
        //parameters['switch'] = $('select[name=switch]').val();
        parameters['utc'] = $('select[name=utc]').val();
        parameters['minutes'] = $('select[name=minutes]').val()*10;
        parameters['mappa'] = $('select[name=mappa]').val();
        data = $('input[name=date]').val();
        num = parameters['utc'];
        utc = (num.toString().length < 2 ? "0"+num : num ).toString();
        parameters['utc'] = utc;
        data = data.replace(new RegExp('-', 'g'), '') + 'Z' + utc + parameters['minutes'];
        parameters['date'] = data;

        return parameters;
      }

      function replace_image(id_trigged_element) {
        values = [];
        setTimeout(function(){
          values = get_form_values();
          //console.log(values);
          tipomappa="map";
          if (values['mappa']=="technical") {
            tipomappa="plot";
          } else {
            tipomappa="map";
          }
          url_call = api_url_base + "/products/" + values['product'] + "/radar/" + values['place'] + "/" + tipomappa +"?output="+values['output']+"&date="+ values['date'];

          $.ajax({
            url: url_call,
            statusCode: {
              500: function() {
                $('#ajax-loader-marker').hide();
                $(".img-radar").replaceWith("<p class='img-radar'>Internal server error</p>");
              }
            }
          }).done(function (data) {
            $('#ajax-loader-marker').hide();
            if (data.map.link) {
              src_image = data.map.link;
              console.log(src_image);
              $(".img-radar").replaceWith("<img class='img-radar' src='" + src_image + "'>");
              $(".legend-left").attr("src", api_url_base+'/products/'+ values['product']+'/radar/legend/left/'+values['output']+'?width=64&height=563&date='+values['date']);
              $(".legend-right").attr("src", api_url_base+'/products/'+ values['product']+'/radar/legend/right/'+values['output']+'?width=64&height=563&date='+values['date']);
              $(".legend-bottom").attr("src", api_url_base+'/products/'+ values['product']+'/radar/legend/bottom/'+values['output']+'?width=64&height=73&date='+values['date']);
            } else{
              $(".img-radar").replaceWith("<p class='img-radar'>No image</p>");
              $(".legend-left").attr("src", '');
              $(".legend-right").attr("src", '');
              $(".legend-bottom").attr("src", '');
            }

          });

        }, 2000);


        /*
        se il prodotto che ho selezionato ha l'output precedente allora lo prendo altrimenti prendo sempre gen
        if(id_trigged_element == 'product') {

          if (!$('select[name=output] option[value="' + desiredOption + '"]').length) {
            console.log('non ho trovato l output precedente');
            values['place'] = 'gen';
          }
          else {
            console.log('outputs disponibile');
          }

          console.log('setto il valore gen');
          $('select[name=output] select').val("gen");
          values['output'] = 'gen';
        }
        */



      }

      function redirect_to_radar_type(){
        parameters = get_form_values();

      }


      Drupal.behaviors.behaviors_radar = {
        attach: function (context, settings) {


          $(document).once('body').each(function(){
          });



          $('.radar-form.form-radar select').once('.radar-form').each(function () {
            $(this).on('change', this, function (event) {
                $('#ajax-loader-marker').show();
                replace_image(event.target.name);
            });


            $('#edit-place').focusout(function(event){
              $('#ajax-loader-marker').show();

              replace_image(event.target.name);
            });
          });
          /*
          $("edit-date").datepicker({
            onSelect: function(dateText) {
              alert("Selected date: " + dateText + "; input's current value: " + this.value);
            }
          }).on("change", function() {
            alert('ciccio');
          });
          */
        }

      }


    }
)(jQuery, Drupal, drupalSettings);
