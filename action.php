<p>
	<button class="bottone" id="Male">Uomo</button>
</p> 
<p>
	<button class="bottone" id="Female">Donna</button>
</p> 
<p>
	<button class="bottone" id="empty">Vuoto</button>
</p> 

<script src="./jquery.min.js"></script>
 <script>
 
  $(".bottone").click(function(e) {
      e.preventDefault();
      var tipo = $(this).attr("id");

      $.ajax({
		url: "script/evento_web.php?tipo="+tipo+"&data="+ new Date().getTime(),
		method: "POST",
		
		
		success: function (json_risposta) {
			console.log(json_risposta);
		},
		
	});

 });
 
  </script>