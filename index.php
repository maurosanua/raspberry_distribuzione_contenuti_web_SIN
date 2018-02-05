 <iframe id="iframe1" src="https://app.innovafarmacia.it/sites/mastersumo/monitor/pfizer/thermacare" style="width:512px;height:256px;"></iframe> 
 
 <button id="bottone">Prova</button>
 
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
 <script>
 
  $("#bottone").click(function(e) {
      e.preventDefault();
      var src = "https://app.innovafarmacia.it//sites/mastersumo/monitor/pfizer/viagra";

      $('#iframe1').fadeOut(1000,function(){
          $('#iframe1').attr('src',src ).load(function(){
              $(this).fadeIn(1000);    
          });
      });

 });
 
 </script>