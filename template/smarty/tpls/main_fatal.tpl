{include file="common/header.tpl"}


<div class="container">
<div class="card">
  <div class="card-header">
    <b>ERROR FATAL - UNHANDLED EXCEPTION</b>
  </div>
  
  <div class="card-body">
	<form method="POST">

	
    <p class="card-text">
	<pre style="border: solid 1px #ccc; border-radius: 3px; background-color: #efefef; padding: 5px;">{trim($error)}</pre>


	</p>
    
  </div>
</div>

</div>


{include file="common/footer.tpl"}