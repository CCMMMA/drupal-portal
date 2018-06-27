
var api_url_base = "http://193.205.230.6";

(function ($, Drupal, drupalSettings) {
      //functions
      function get_form_values() {
        parameters = [];
        place = $('#edit-place').val();
        place = place.substring(
            place.lastIndexOf("- ") + 1,
            place.lastIndexOf("(")
        );
        parameters['place'] = place.replace(/\s/g, "");

        parameters['product'] = $('select[name=product]').val();
        parameters['output'] = $('select[name=output]').val();
        data = $('input[name=date]').val();
        data = data.replace(new RegExp('-', 'g'), '') + 'Z' + $('select[name=utc]').val() + '00';
        parameters['data'] = data;
        parameters['utc'] = $('select[name=utc]').val();

        return parameters;
      }

      function replace_image(id_trigged_element) {
        values = [];
        setTimeout(function(){
          values = get_form_values();
          console.log(values);
          url_call = api_url_base + "/products/" + values['product'] + "/forecast/" + values['place'] + "/map?output="+values['output']+"&date="+ values['data'];
          console.log(url_call);

          $.ajax({
            url: url_call,
            statusCode: {
              500: function() {
                $('#ajax-loader-marker').hide();
                $(".img-forecast").replaceWith("<p class='img-forecast'>Internal server error</p>");
              }
            }
          }).done(function (data) {
            $('#ajax-loader-marker').hide();
            if (data.map.link) {
              src_image = data.map.link;
              //console.log(src_image);
              $(".img-forecast").replaceWith("<img class='img-forecast' src='" + src_image + "'>");
            } else{
              $(".img-forecast").replaceWith("<p class='img-forecast'>No image</p>");
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


      Drupal.behaviors.behaviors_forecast = {
        attach: function (context, settings) {


          $(document).once('body').each(function(){
            //manage trobber
            trobber_markup = "<div id='ajax-loader-marker' style='width: 100%; text-align: center; display: none'><img id='ajax_loader' style='width: 3%;' src="+ window.location.protocol + "//" + window.location.host + "/" +"sites/all/themes/zircon_custom/images/ajax-loader.gif></div>";
            $('.img-forecast').before(trobber_markup);
          });

          $('#forecast-form select').once('#forecast-form').each(function () {
            $(this).change(function (event) {
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