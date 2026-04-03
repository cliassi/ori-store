<?php
$obj = R::dispense('refund');
if (defined('ID')) {//store.apurewater.com/store/collection/add?customer=82
    $obj = R::load('refund', ID);
}
if (isset($post->save)) {
    try {
        $obj->supplier_id = $post->supplier_id;
        $obj->created_by = uid();
        $obj->date = $post->date;
        $obj->amount = $post->amount;
        $obj->payment_method = $post->payment_method;
        $obj->description = $post->description;
        //$obj->branch_id = $branch_id;
        R::store($obj);

        if (count($_FILES) > 0) {
            if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
                $file = upload($_FILES, 'image' . $obj->id . "-" . time(), 'uploads', 'image');
                $obj->image = "uploads/$file";
            }
            if (isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])) {
                $file = upload($_FILES, 'logo' . $obj->id . "-" . time(), '../uploads', 'logo');
                $obj->logo = "uploads/$file";
            }
            R::store($obj);
        }
        print "<script>location.href = '".ROOT."/supplier/details/$obj->supplier_id'; </script>";
    } catch (\Throwable $th) {
        dump($th);
    }

}
?>

        <!-- [ Main Content ] start -->
        <div class="row">
          <!-- [ form-element ] start -->
          <div class="col-md-3"></div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5>New refund</h5>
              </div>
              <div class="card-body">
                <form class="forms-sample" method="post" enctype="multipart/form-data">
                  <div class="row g-4">
                    <?php
                      $formItems = [
                        'supplier_id' => ['col' => 6, 'label' => 'Supplier', 'type' => 'dropdown', 'value'=>(isset($get->supplier) ? $get->supplier : $obj->supplier_id), 'table'=>'supplier', 'textField'=>'company'],
                        'date' => ['col' => 6, 'label' => 'Date', 'type' => 'date2', 'value' => $obj->date],
                        'amount' => ['col' => 6, 'label' => 'Amount', 'type' => 'text', 'value'=>$obj->contact],
                        'payment_method' => ['col' => 6, 'label' => 'Payment Method', 'type' => 'radio', 'value'=>$obj->payment_method, 'class'=>'payment_method', 'options'=>['Supplier ID', 'Bank']],
                        'a' => ['col' => 12, 'label' => 'Amount', 'type' => 'buttons', 'class'=>'btn btn-success btn-amount', 'target'=>'amount', 'options'=>[100,200,300,500,1000,1500]],
                        'description' => ['col' => 12, 'label' => 'Particulars', 'type' => 'textarea', 'value' => $obj->address],
                        'l' => ['col' => 12, 'label' => 'Amount', 'type' => 'radios', 'target'=>'amount', 'options'=>[
                            "SeaMaster Supplier Refund Koresee Rm: "
                          ],
                          'class'=>'notes'
                        ],
                      ];

                      print buildForm($formItems);

                    ?>
                  </div>

                  <div class="d-grid gap-2 mt-2">
                    <button class="btn btn-primary" name='save' type="submit">Save Payment</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

<script type="text/javascript">
  $(".radio-label input").attr('checked', true);

  $(".btn-amount").click(function(){
    $("#amount").val($(this).text());
    setParticulars();
  });
  $(".notes").click(setParticulars);

  function setParticulars(){
    debugger;
    const supp = $("#supplier_id option:selected").text();
    const notes = $('input[type="radio"].notes:checked').parent().text().replace('[SUP]', supp + ' ke');
    const amount = $("#amount").val();

    if(notes.includes('bank')){
      $('input:radio[name=payment_method]:nth(0)').removeAttr('checked');
      $('input:radio[name=payment_method]:nth(1)').attr('checked',true);
    } else{
      console.log(notes);
      $('input:radio[name=payment_method]:nth(1)').removeAttr('checked');
      $('input:radio[name=payment_method]:nth(0)').attr('checked',true);
    }
    $("#description").val(notes + amount);
  }
</script>        