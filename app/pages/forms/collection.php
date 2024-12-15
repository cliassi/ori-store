<?php
$obj = R::dispense('collection');
if (defined('ID')) {
    $obj = R::load('collection', ID);
}
if (isset($post->save)) {
    try {
        $obj->customer_id = $post->customer_id;
        $obj->date = $post->date;
        $obj->amount = $post->amount;
        $obj->payment_method = $post->payment_method;
        $obj->description = $post->description;

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
        print "<script>location.href = '".ROOT."/customer/details/$obj->customer_id'; </script>";
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
                <h5>New Collection</h5>
              </div>
              <div class="card-body">
                <form class="forms-sample" method="post" enctype="multipart/form-data">
                  <div class="row g-4">
                    <?php
                      $formItems = [
                        'customer_id' => ['col' => 6, 'label' => 'Customer', 'type' => 'dropdown', 'value'=>($obj->customer_id?$obj->customer_id:(isset($get->customer)?$get->customer:0)), 'table'=>'customer', 'textField'=>'company'],
                        'date' => ['col' => 6, 'label' => 'Date', 'type' => 'date2', 'value' => $obj->date],
                        'amount' => ['col' => 6, 'label' => 'Amount', 'type' => 'text', 'value'=>$obj->contact],
                        'payment_method' => ['col' => 6, 'label' => 'Payment Method', 'type' => 'radio', 'value'=>$obj->payment_method, 'class'=>'payment_method', 'options'=>['Cash', 'Bank']],
                        'a' => ['col' => 12, 'label' => 'Amount', 'type' => 'buttons', 'class'=>'btn btn-success btn-amount', 'target'=>'amount', 'options'=>[100,200,300,500,1000,1500]],
                        'description' => ['col' => 12, 'label' => 'Particulars', 'type' => 'textarea', 'value' => $obj->address],
                        'l' => ['col' => 12, 'label' => 'Amount', 'type' => 'radios', 'target'=>'amount', 'options'=>[
                            "Apure water may bank account a taka banking korse Rm :",
                            "Neat & Clean may bank account a taka banking korse Rm:",
                            "Neat & Clean RHB bank account a taka banking korse Rm:",
                            "Ddcon may bank account a taka banking korse Rm:",
                            "Bdcon may bank account a taka banking korse Rm:",
                            "BdpZone May Bank Account a Taka Banking Korse Rm:",
                            "Ekawin may bank account a taka banking korse Rm:",
                            "Khandaker Tajul may bank account a taka banking korse Rm:",
                            "Cash Collection kora hoyese Rm:"
                          ],
                          'class'=>'notes'
                        ],
                      ];

                      print buildForm($formItems);

                    ?>
                  </div>
                  <br><br>
                  <div class="d-grid gap-2 mt-2">
                    <div class="row g-4">
                      <div class='col-6 px-5'><button class="btn btn-primary w100p" name='save' type="submit">Save Collection</button></div>
                      <div class='col-6 px-5'><button class="btn btn-danger w100p" name='save' type="reset">Reset</button></div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

<script type="text/javascript">
  $(".btn-amount").click(function(){
    $("#amount").val($(this).text());
    setParticulars();
  });
  $(".notes").click(setParticulars);

  function setParticulars(){
    const notes = $('input[type="radio"].notes:checked').parent().text();
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