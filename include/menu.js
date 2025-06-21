
 $(document).ready(function(){ 
  
  $(".submenu").click(function(){ 
  $(this).children("ul").slideToggle(); 
  
  var Grupo = $(this).parent();
  var posicion = $(this).index();
  Grupo.children().each(function (index) {
   if (index != posicion)
    $(this).children("ul").slideUp("slow");
  });
 })
 
  $("ul").click(function(p){ 
  p.stopPropagation(); 
 });
 
 
})