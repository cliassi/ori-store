  <div class="row">
    <div class="col-sm-12">
      <div class="card p-5">
        <?php
         if(METHOD=='add'){
          require_once 'forms/'.PAGE.'.php';
         } elseif(METHOD=='remove'){
            if(isset($get->conf)){    
              $object = R::load("expense_account", ID);
              R::trash($object);
              redir("../view");
            } else{
              ?>
              <script type="text/javascript">
                if(confirm("Are you sure you want to completly remove this Expense Account Entry?")){
                  location.href = "?conf";
                } else{
                  location.href = "../view";  
                }
              </script>
              <?php
            }
         } else {
          require_once 'details/'.PAGE.'.php';
        }
      ?>
    </div>
  </div>
</div>