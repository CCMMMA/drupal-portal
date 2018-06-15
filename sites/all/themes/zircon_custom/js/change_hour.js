/*
    
        var time_now_temp = moment().format();
        var time_now = time_now_temp.replace(/-/g, '');
        time_now = time_now.replace(/T/g, 'Z');
        //prendo solo primi 11 caratteri
        time_now = time_now.substring(0,11);
        console.log(time_now);
        //col-md-4 wrf
        if($( ".col-md-4.wrf .img-box" ).length != 0){
          img = '<img id="imgfor" src="http://meteo.uniparthenope.it/render/map.php?prod=wrf3&amp;place=reg15&amp;output=gen&amp;date='+time_now+'">';
          $(".col-md-4.wrf .img-box").html( img );
        }
        //col-md-4 ww3
        if($( ".col-md-4.ww3 .img-box" ).length != 0){
          img = '<img id="imgfor" src="http://meteo.uniparthenope.it/render/map.php?prod=ww33&amp;place=reg15&amp;output=gen&amp;date='+time_now+'">';
          $(".col-md-4.ww3 .img-box").html( img );
        }
        //col-md-4 chimere
        
        if($( ".col-md-4.chimere .img-box" ).length != 0){
          img = '<img id="imgfor" src="http://meteo.uniparthenope.it/render/map.php?prod=chm3&place=reg15&output=caqi&date='+time_now+'"><img id="bar_right" src="http://blackjeans.uniparthenope.it/prods/getbar.php?model=chm3&amp;position=v&amp;output=caqi" />';
          $(".col-md-4.chimere .img-box").html( img );
        }
        */