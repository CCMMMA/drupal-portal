var base_url="https://api.meteo.uniparthenope.it"
var prod="wrf5";
var place="it000";
var type="plot";
var urls=new Array();
var index=0;
var indexVisible=0;
var tempo=100;
var fermo=10;
var quanteCaricate=0;
function animateHTML() {
  console.log("animate");
  index++;
  if (index<urls.length) {
    jQuery("#anim-"+index).css("opacity","1").css("visibility","visible");
    jQuery("#anim-"+indexVisible).css("opacity","0");
    indexVisible=index;
  }
  if (index>=(urls.length+fermo)) {
    index=0;
  }
}
function preloadHTMLImages(array){
  var contenitore=jQuery('#animazione');
  var imga='<img src="';
  var imgb='" id="anim-';
  var imgc='" border="1" style="opacity: 0;">';
  jQuery('#segnaposto').on("load",caricata).attr("src",array[0]);
  for (var i=0; i < array.length; i++){
    contenitore.append(imga+imgb+i+imgc);
    jQuery('#anim-'+i).on("load",caricata).attr("src",array[i]);
  }
}
function caricata(){
  quanteCaricate++;
  if (quanteCaricate==urls.length){
    // può iniziare l'animazione
    jQuery('#anim').css("visibility","hidden");
    index=0;
    jQuery("#anim-"+index).css("opacity","1").css("visibility","visible");
    indexVisible=index;
    setInterval('animateHTML()', tempo);
  }
}

(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      $(window).once().on('load scroll', function () {
  $.getJSON(base_url+"/products/"+prod+"/"+place+"/avail", function(data){
    var avails=data.avail;
    $.each(avails, function (index, value) {
      var url=base_url+"/products/"+prod+"/forecast/"+place+"/"+type+"/image?date="+value.date;
      urls.push(url);
      console.log(url);
      //$.getJSON(url, function(response){});
    });
    preloadHTMLImages(urls);
  });
});
    }
  };
})(jQuery, Drupal);
