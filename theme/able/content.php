<link rel="stylesheet" type="text/css" href="/store/assets/splide/css/splide.min.css">
<script src="/store/assets/splide/js/splide.min.js"></script>
<style type="text/css">
  .splide__pagination{
    display: none;
  }
  span.cart{
  	margin-right: 50px; 
  }
  @media only screen and (max-width: 600px) {
  	span.cart{
  		margin-right: 20px; 
	  }
	}
</style>
<div class="row no-print">
	<div class='col-2'></div>
	<div class='col-8'>
		<section class="splide" aria-label="Splide Basic HTML Example" style="overflow: hidden">
		  <div class="splide__track">
				<ul class="splide__list">
					<?php
						$products = R::find('product');
						foreach($products as $product){
							// print "<span class='nav-product'><a href='/store/product/sell#product-$product->id'><img src='".ROOT."/$product->image' height='64px'></a></span>";
							print "<li class='splide__slide'><a href='/store/product/sell#product-$product->id'><img src='".ROOT."/$product->image' height='64px'></a></li>";
						}
					?>
				</ul>
		  </div>
		</section>
	</div>
	<div class='col-2'>
		<a data-bs-toggle='modal' data-bs-target='#orderModal'>
	    <span class='cart' style="float: right; font-size: 3rem; margin-top: -15px;"><i class='fas fa-shopping-cart'></i></span>
	  </a>
  </div>
</div>

<div class='product-slider'>
<span style="width:15px;display: inline-block;"></span>
<?php
/*
$menus = [
      'customer'=>'Customer +', 
      'product'=>'Product +', 
      'report/daily'=>'Daily Sales Collection', 
      'expense'=>'Expense', 
      'report/order'=>'Daily Order', 
      'report/purchase'=>'Daily Purchase', 
      'report/due'=>'Customer Due Reports',
];
	// dd($menu_content);
print "<ul class='pc-navbar'>";
print "</ul>";
?>
<div class="btn-group mb-2 me-2 ml-5" style="margin-left: 15px">
  <button class="btn btn-success dropdown-toggle store-menu" style="border-radius: 5px;padding: 15px 20px;" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Store</button>
  <div class="dropdown-menu" style="margin-top:-13px">
  	<?php
  		foreach ($menus as $key => $value) {
  			print "<a class='dropdown-item' href='$key'>$value</a>";
  		}
  	?>
    <!-- <a class="dropdown-item" href="#!">Action</a>
    <a class="dropdown-item" href="#!">Another action</a>
    <a class="dropdown-item" href="#!">Something else here</a> -->
  </div>
</div>
<?php
*/
$products = R::find('product');
foreach($products as $product){
	//print "<span class='nav-product'><a href='/store/product/sell#product-$product->id'><img src='".ROOT."/$product->image' height='64px'></a></span>";
}
?>
</div>

<form method='post' id='form-delete'>
	<input type='hidden' id='id-to-delete' name='idToDelete'>
</form>
<form method='post' id='form-delete-2'>
	<input type='hidden' id='id-to-delete-2' name='idToDelete2'>
</form>
<form method='post' id='form-delete-3'>
	<input type='hidden' id='id-to-delete-3' name='idToDelete3'>
</form>
<?php require_once 'app/pages/' . $page . '.php'; ?>

<script type="text/javascript">

	var splide = new Splide( '.splide', {
	  type   : 'loop',
	  drag   : 'free',
	  snap   : true,
	  height   : '10rem',
	  focus    : 'center',
	  autoWidth: true,
	  arrows: false
	} );

	splide.mount();
	<?php
	if(METHOD == 'sell2' || METHOD == 'sell'){
		$products = R::find('product');
      foreach($products as $product){
      	print "var splide{$product->id} = new Splide( '.splide{$product->id}', {
				  drag   : 'free',
				  snap   : true,
				  height   : '10rem',
				  focus    : 'center',
				  autoWidth: true,
				  arrows: false
				} );

				splide{$product->id}.mount();";
      }
	}
	?>
	

	function deleteConfirmation(id){
		if(confirm("Are you sure?")){
			$("#id-to-delete").val(id);
			$("#form-delete").submit();
		}
	}
	function deleteConfirmation2(id){
		if(confirm("Are you sure?")){
			$("#id-to-delete-2").val(id);
			$("#form-delete-2").submit();
		}
	}
	function deleteConfirmation3(id){
		if(confirm("Are you sure?")){
			$("#id-to-delete-3").val(id);
			$("#form-delete-3").submit();
		}
	}

	$(".radio-label span").click(function(){
		$(this).parent().find('input').trigger('click');
	});

	$(document).ready(function () {
  $('.protected-link').on('click', function (e) {
	    e.preventDefault(); // Prevent the default action of the link
	    
	    const targetUrl = $(this).attr('href'); // Get the href of the clicked link
	    token = "";
	    // Show SweetAlert2 prompt
	    Swal.fire({
	      title: 'Enter PIN',
	      input: 'text',
	      inputLabel: 'Please enter the PIN to proceed',
	      inputPlaceholder: 'PIN',
	      showCancelButton: true,
	      confirmButtonText: 'Submit',
	      preConfirm: (pin) => {
	        return new Promise((resolve, reject) => {
	          // Call the endpoint to validate the PIN
	          $.post('/store/ajax/checkpin.php', { pin: pin })
	            .done((response) => {
	              if (response.length > 10) { // Assuming the server returns '1' for success
	              	token = response;
	                resolve();
	              } else {
	                Swal.showValidationMessage('Incorrect PIN');
	                // Re-enable the Submit button
	                Swal.getConfirmButton().disabled = false;
	                reject();
	              }
	            })
	            .fail(() => {
	              Swal.showValidationMessage('Error validating PIN');
	                // Re-enable the Submit button
	                Swal.getConfirmButton().disabled = false;
	              reject();
	            });
	        });
	      },
	    }).then((result) => {
	      if (result.isConfirmed) {
	        // If validation is successful, redirect to the target URL
	        window.location.href = targetUrl + "&token=" + token;
	      }
	    });
	  });
	});


</script>