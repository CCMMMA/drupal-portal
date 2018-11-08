
(function ($, Drupal, drupalSettings) {

      function get_form_values() {
        parameters = [];
        place = $('#edit-place').val();
        place = place.substring(
            place.lastIndexOf("- ") + 1,
            place.lastIndexOf("(")
        );
        parameters['place'] = place.replace(/\s/g, "");
        parameters['switch'] = $('select[name=switch]').val();

        /*
                parameters['product'] = $('select[name=product]').val();
                parameters['output'] = $('select[name=output]').val();
                parameters['switch'] = $('select[name=switch]').val();
                parameters['utc'] = $('select[name=utc]').val();
                data = $('input[name=date]').val();
                num = parameters['utc'];
                utc = (num.toString().length < 2 ? "0"+num : num ).toString();
                parameters['utc'] = utc;
                data = data.replace(new RegExp('-', 'g'), '') + 'Z' + utc + '00';
                parameters['data'] = data;
        */
        return parameters;
      }

      function redirect_to_forecast_type(){
        parameters = get_form_values();
        forecast_type = parameters['switch'];
        if(forecast_type != 'table'){
         // args = 'product='+parameters['product'] + '&place=' + parameters['place'] + '&output=' + parameters['output'] + '&date=' + parameters['data'] + '&utc=' + parameters['utc'];
          args = '&place=' + parameters['place'];
          $(window.location).attr('href', window.location.protocol + "//" + window.location.host + "/" + 'forecast/' + forecast_type + '?' + args);
        }

      }

    Drupal.behaviors.behaviors_forecast = {
      attach: function (context, settings) {

        $('#forecast-table-form select').once('#forecast-table-form').each(function () {
          $(this).change(function (event) {
            if (event.target.name == 'switch') {
              redirect_to_forecast_type();
            }
          });

        });
      }
    }
  }
)(jQuery, Drupal, drupalSettings);