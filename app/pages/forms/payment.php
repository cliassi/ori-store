<?php
ensureMysqlColumn('payment', 'opex_or_capex', "VARCHAR(20) NULL DEFAULT 'Capex'");
ensureMysqlColumn('expense_account_entry', 'opex_or_capex', "VARCHAR(20) NULL DEFAULT 'Capex'");
ensureMysqlColumn('payment', 'investment_id', "INT NULL DEFAULT NULL");
ensureMysqlColumn('expense_account_entry', 'investment_id', "INT NULL DEFAULT NULL");

if (!function_exists('createFormInvestmentEntry')) {
  function createFormInvestmentEntry($date, $amount, $particulars, $paymentMethod, $investmentId = 0)
  {
    $investment = $investmentId > 0 ? R::load('investment', (int) $investmentId) : R::dispense('investment');
    $investment->date = nn($date) ? $date : today();
    $investment->amount = (float) $amount;
    $investment->particulars = (string) $particulars;
    $investment->payment_method = in_array(strtolower((string) $paymentMethod), ['bank', 'online'], true) ? 'Bank' : 'Cash';
    if (!$investment->id) {
      $investment->created_by = uid();
      $investment->created_at = now();
      $investment->trash = 0;
    }
    R::store($investment);
    return (int) $investment->id;
  }
}

$obj = R::dispense('payment');
if (defined('ID')) {
  $obj = R::load('payment', ID);
}
if (isset($post->save)) {
  try {
    $obj->supplier_id = $post->supplier_id;
    $obj->created_by = uid();
    $obj->date = $post->date;
    $obj->amount = $post->amount;
    $obj->payment_method = $post->payment_method;
    $obj->description = $post->description;
    $obj->branch_id = $branch_id;

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
    print "<script>location.href = '" . ROOT . "/supplier/details/$obj->supplier_id'; </script>";
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
        <h5>New Payment</h5>
      </div>
      <div class="card-body">
        <form class="forms-sample" method="post" enctype="multipart/form-data">
          <div class="row g-4">
            <?php
            $formItems = [
              'supplier_id' => ['col' => 6, 'label' => 'Supplier', 'type' => 'dropdown', 'value' => (isset($get->supplier) ? $get->supplier : $obj->supplier_id), 'table' => 'supplier', 'textField' => 'company'],
              'date' => ['col' => 6, 'label' => 'Date', 'type' => 'date2', 'value' => $obj->date],
              'amount' => ['col' => 6, 'label' => 'Amount', 'type' => 'text', 'value' => $obj->contact],
              'payment_method' => ['col' => 6, 'label' => 'Payment Method', 'type' => 'radio', 'value' => $obj->payment_method, 'class' => 'payment_method', 'options' => ['Cash', 'Bank'], 'required' => true],
              'a' => ['col' => 12, 'label' => 'Amount', 'type' => 'buttons', 'class' => 'btn btn-success btn-amount', 'target' => 'amount', 'options' => [100, 200, 300, 500, 1000, 1500]],
              'description' => ['col' => 12, 'label' => 'Particulars', 'type' => 'textarea', 'value' => $obj->address],
              'l' => [
                'col' => 12,
                'label' => 'Amount',
                'type' => 'radios',
                'target' => 'amount',
                'options' => [
                  "Apure water may bank account theke [SUP] payment deoya hoyese Rm :",
                  "Neat & Clean may bank account theke [SUP] payment deoya hoyese Rm:",
                  "Neat & Clean RHB bank account theke [SUP] payment deoya hoyese Rm:",
                  "Ddcon may bank account theke [SUP] payment deoya hoyese Rm:",
                  "Bdcon may bank account theke [SUP] payment deoya hoyese Rm:",
                  "BdpZone May Bank Account theke [SUP] payment deoya hoyese Rm:",
                  "Ekawin may bank account theke [SUP] payment deoya hoyese Rm:",
                  "Khandaker Tajul may bank account theke [SUP] payment deoya hoyese Rm:",
                  "Petty Cash theke [SUP] Payment deoya hoyese Rm:"
                ],
                'class' => 'notes'
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
    $(".btn-amount").click(function () {
      $("#amount").val($(this).text());
      setParticulars();
    });
    $(".notes").click(setParticulars);

    function setParticulars() {
      debugger;
      const supp = $("#supplier_id option:selected").text();
      const notes = $('input[type="radio"].notes:checked').parent().text().replace('[SUP]', supp + ' ke');
      const amount = $("#amount").val();

      if (notes.includes('bank')) {
        $('input:radio[name=payment_method]:nth(0)').removeAttr('checked');
        $('input:radio[name=payment_method]:nth(1)').attr('checked', true);
      } else {
        console.log(notes);
        $('input:radio[name=payment_method]:nth(1)').removeAttr('checked');
        $('input:radio[name=payment_method]:nth(0)').attr('checked', true);
      }
      $("#description").val(notes + amount);
    }

    // ✅ HARD VALIDATION ON SUBMIT (THIS FIXES YOUR PROBLEM)
    $("form").on("submit", function (e) {

      const paymentMethodChecked = $('input[name="payment_method"]:checked').length > 0;
      const notesChecked = $('input.notes:checked').length > 0;

      if (!paymentMethodChecked) {
        e.preventDefault();

        Swal.fire({
          icon: 'error',
          html: `
            ⚠️ Please select :<br><br>
            1. Payment Method
          `
        });

        return false;
      }

    });
  </script>