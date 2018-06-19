
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

        parameters['product'] = $('#edit-product').val();
        parameters['output'] = $('#edit-output').val();
        data = $('#edit-date').val();
        data = data.replace(new RegExp('-', 'g'), '') + 'Z' + $('#edit-utc').val() + '00';
        parameters['data'] = data;
        parameters['utc'] = $('#edit-utc').val();

        return parameters;
      }

      function replace_image() {
        values = get_form_values();
        url_call = api_url_base + "/products/" + values['product'] + "/forecast/" + values['place'] + "/map";
        //console.log(url_call);

        $.ajax({
          url: url_call,
        }).done(function (data) {
          if ((data.map.link.length != 0)) {
            src_image = data.map.link;
            //console.log(src_image);
            $("img.img-forecast").replaceWith("<img class='img-forecast' src='" + src_image + "'>");
          }

        });

      }


      Drupal.behaviors.behaviors_forecast = {
        attach: function (context, settings) {


          $('#forecast-form select').once('#forecast-form').each(function () {
            $(this).change(function () {
              //console.log('cambio');
              replace_image();
            });
          });
        }
      }

    }
)(jQuery, Drupal, drupalSettings);