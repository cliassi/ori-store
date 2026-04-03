<?php
session_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

extract($_POST);
$page = 'customer';
$storeBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'); // => /store
$pageBase = $storeBase . '/' . $page; // => /store/customer
// auto-detect "/store" (because this file is /store/ajax/search_customer.php)

$fields = [
  "id" => '',
  "code" => ["label" => "Code", "display" => ''],
  "company" => ["label" => "Shop Name", "display" => '', 'link' => 'details'],
  "contact" => ["label" => "Contact Person", "display" => ''],
  "mobile" => ["label" => "C.P. Mobile", "display" => ''],
  "city" => ["label" => "<a href='{$storeBase}/area'>Area</a>", "display" => ''],
  // "address" => ["label"=>"Shop Address", "display"=>''],
  "image" => ["label" => "Photo", "display" => '', 'type' => 'image'],
  "" => ["display" => '', 'type' => 'link', 'action' => 'edit'],
];
$objs = select('*', 'customer', "company LIKE '%$key%' OR code LIKE '%$key%' OR contact LIKE '%$key%' OR mobile LIKE '%$key%' OR city LIKE '%$key%'");
$contant = "";
?>
<div class="row">
  <!-- Zero config table start -->
  <div class="col-sm-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <h5>Customer List</h5>
          </div>
        </div>

      </div>
      <div class="card-body">
        <div class="dt-responsive table-responsive">
          <table id="customer-table" class="table table-striped table-bordered nowrap">
            <thead>
              <tr>
                <th>Sl.</th>
                <?php foreach ($fields as $key => $value)
                  if (isset($value['label']))
                    print "<th>{$value['label']}</th>"; ?>
                <th><a href="<?php print $pageBase; ?>/add"><i class='fa fa-file'></i> Add</a></th>
              </tr>
            </thead>
            <tbody>
              <?php
              $i = 1;
              while ($obj = mysqli_fetch_object($objs)) {
                print "<tr>";
                print "<td>" . ($i++) . "</td>";
                foreach ($fields as $key => $value) {
                  if (isset($value['display'])) {
                    $printed = false;
                    if (isset($value['type'])) {
                      if ($value['type'] == "image") {
                        print "<td height='60px'>" . ($obj->$key ? "<a data-lightbox='{$obj->$key}'><img src='{$obj->$key}' height='60px'></a>" : "") . "</td>";
                        $printed = true;
                      } elseif ($value['type'] == "link") {
                        print "<td><a href='$pageBase/edit/$obj->id'><i class='fa fa-edit'></i></a>";
                        if (uid() == 1) {
                          print space(5) . "<a href='?del&id=$obj->id' class='protected-link'><i class='fas fa-trash-alt'></i></a><td></td>";
                        }
                        $printed = true;
                      }
                    }
                    if (!$printed) {
                      print "<td>";
                      if (isset($value['link']))
                        print "<a href='$pageBase/{$value['link']}/$obj->id'>";
                      if ($key == 'code' && nn($obj->location)) {
                        print "<a href='$obj->location' target='_blank'>" . $obj->$key . "</td>";
                      } else {
                        print $obj->$key;
                      }
                      if (isset($value['link']))
                        print "</a>";
                      print "</td>";
                    }
                  }
                }
                print "</tr>";
              }
              ?>
            </tbody>
            <tfoot>
              <!-- <tr>
                        <th>Comapny Name</th>
                        <th>Contact Person</th>
                        <th>C.P. Mobile</th>
                        <th>Shop Address</th>
                        <th></th>
                      </tr> -->
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>