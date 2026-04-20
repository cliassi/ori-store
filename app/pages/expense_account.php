<div class="row">
  <div class="col-sm-12">
    <div class="card p-5">
      <?php

      if (METHOD == 'add' || METHOD == 'edit') {
        require_once 'forms/' . PAGE . '.php';
      } elseif (METHOD == 'remove') {
        if (isset($get->conf)) {
          $object = R::load("expense_account", ID);

          // Check if expense account has associated entries (for all users including admin)
          $hasEntriesQuery = "SELECT COUNT(*) as count FROM expense_account_entry WHERE accountpath LIKE CONCAT(?, '%') OR accountid = ?";
          $hasEntriesResult = R::getRow($hasEntriesQuery, [$object->path, ID]);
          $hasEntries = $hasEntriesResult['count'] > 0;

          if ($hasEntries) {
            // Show error message and redirect back
            ?>
            <script type="text/javascript">
              alert("Expense Account Entry has previous month data therefore you cannot delete it.");
              var redirectUrl = "/store/expense_account/carwash";
              var urlParams = new URLSearchParams(window.location.search);
              var tParam = urlParams.get('t');
              var companyParam = urlParams.get('company');
              if (tParam === 'opex') {
                redirectUrl = "/store/expense_account/carwash?company=" + (companyParam || '3') + "&t=opex";
              } else if (tParam === 'capex') {
                redirectUrl = "/store/expense_account/carwash?company=" + (companyParam || '1') + "&t=capex";
              }
              location.href = redirectUrl;
            </script>
            <?php
            exit;
          }

          // Delete related expense_account_entry records before deleting expense_account
          // to avoid foreign key constraint violation
          R::exec("DELETE FROM expense_account_entry WHERE accountid = ?", [ID]);
          // Also delete entries where accountpath matches this account's path
          $accountPath = $object->path;
          R::exec("DELETE FROM expense_account_entry WHERE accountpath LIKE CONCAT(?, '%')", [$accountPath]);
          R::trash($object);
          // Preserve opex/capex filter in redirect with proper URL format
          $redirectUrl = "/store/expense_account/carwash";
          if (isset($_GET['t'])) {
            if ($_GET['t'] == 'opex') {
              $company = isset($_GET['company']) ? $_GET['company'] : '3';
              $redirectUrl = "/store/expense_account/carwash?company=" . $company . "&t=opex";
            } elseif ($_GET['t'] == 'capex') {
              $company = isset($_GET['company']) ? $_GET['company'] : '1';
              $redirectUrl = "/store/expense_account/carwash?company=" . $company . "&t=capex";
            }
          }
          redir($redirectUrl);
        } else {
          ?>
          <script type="text/javascript">
            if (confirm("Are you sure you want to completly remove this Expense Account Entry?")) {
              location.href = "?conf";
            } else {
              var redirectUrl = "/store/expense_account/carwash";
              var urlParams = new URLSearchParams(window.location.search);
              var tParam = urlParams.get('t');
              var companyParam = urlParams.get('company');
              if (tParam === 'opex') {
                redirectUrl = "/store/expense_account/carwash?company=" + (companyParam || '3') + "&t=opex";
              } else if (tParam === 'capex') {
                redirectUrl = "/store/expense_account/carwash?company=" + (companyParam || '1') + "&t=capex";
              }
              location.href = redirectUrl;
            }
          </script>
          <?php
        }
      } else {
        require_once 'details/' . PAGE . '.php';
      }
      ?>
    </div>
  </div>
</div>