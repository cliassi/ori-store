<?php

function upload($files, $name = '', $dir = '', $_name = 'file')
{
    $dir = $dir == '' ? 'uploads_files' : $dir;
    if (!file_exists($dir)) {
        mkdir2($dir);
    }
    if ($name == '') {
        $name = $files[$_name]["name"];
        $filename = $name;
    } else {
        $filename = $name . ext($files[$_name]["name"]);
    }
    //print $filename;
    if ($files[$_name]["error"] > 0) {
        echo "Return Code: " . $files[$_name]["error"] . "<br />";
        $filename = false;
    } else {
        //if (file_exists("$dir/$filename")){
        //echo $files[$_name]["name"] . " already exists. ";
        //$filename=false;
        //} else {
        move_uploaded_file($files[$_name]["tmp_name"], "$dir/$filename");
        //$filename->name = $name.ext($files["file"]["name"]);
        //$filename->url = "uploads/".$name.ext($files["file"]["name"]);
        //echo "<img src='".$path.time().substr($files["file"]["name"],strlen($files["file"]["name"])-4)."' />";
        //}
    }
    return $filename;
}

if (!function_exists('ensureMysqlColumn')) {
    function ensureMysqlColumn($table, $column, $definition)
    {
        global $c;

        $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
        $column = preg_replace('/[^A-Za-z0-9_]/', '', $column);
        $definition = trim((string) $definition);
        if (!$table || !$column || !$definition || !isset($c)) {
            return false;
        }

        $check = $c->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && $check->num_rows > 0) {
            return true;
        }

        return (bool) $c->query("ALTER TABLE `$table` ADD `$column` $definition");
    }
}


function notifyUsers($message, $title = "ORI : Customer Order"){
    $users = R::find('sys_user', 'order_notification=1');
    foreach($users as $user){
        $sub = R::findOne("push_client", "`type`='a' AND id=$user->id");
        if($sub){
            sendPush($sub->uuid, $message, $title);
        }
    }
}

function sendPush($tag, $message, $title = "ORI : Customer Order")
{
    $payload = [
        'tag' => (string)$tag,
        'message' => (string)$message,
        'title' => (string)$title,
    ];

    $url = 'http://localhost:88';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $logFile = __DIR__ . DIRECTORY_SEPARATOR . 'push.log';
    $logPrefix = '[' . date('Y-m-d H:i:s') . '] ';

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        $logLine = $logPrefix
            . "URL=$url | payload=" . json_encode($payload)
            . " | curl_error=" . $err
            . PHP_EOL;
        @file_put_contents($logFile, $logLine, FILE_APPEND);
        curl_close($ch);
        return false;
    }

    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $logLine = $logPrefix
        . "URL=$url | payload=" . json_encode($payload)
        . " | status=$status | response=" . $response
        . PHP_EOL;
    @file_put_contents($logFile, $logLine, FILE_APPEND);

    return [
        'status' => $status,
        'response' => $response,
    ];
}


function convertPdfToImages(string $pdfFilename, string $outputName){
    $pdfPath = __DIR__ . '/' . $pdfFilename;
    $outputBase = __DIR__ . '/' . $outputName;
    $pdftoppm = 'C:\\poppler\\Library\\bin\\pdftoppm.exe'; // Use your actual path

    if (!file_exists($pdfPath)) {
        throw new Exception("PDF file not found: $pdfPath");
    }

    $cmd = "\"$pdftoppm\" -jpeg \"$pdfPath\" \"$outputBase\" 2>&1";
    $output = shell_exec($cmd);

    // Output files will be: outputName-1.jpg, outputName-2.jpg, etc.
    $generatedFiles = glob($outputBase . '-*.jpg');

    // Optionally rename them to remove the -1/-2 suffix if only one page
    if (count($generatedFiles) === 1) {
        $finalName = __DIR__ . '/' . $outputName . '.jpg';
        rename($generatedFiles[0], $finalName);
        return [$finalName];
    }

    return $generatedFiles;
}



function accountEntry($accountid, $particulars, $amount, $type, $data = []){
    global $branch_id;
    $data = (object)$data;
    $entry = R::dispense("expense_account_entry");
    $accountpath = $accountid ? getFieldValue("expense_account", "path", "id=$accountid") : '';
    $entry->accountid = $accountid;
    $entry->amount = $amount;
    $entry->branch_id = $branch_id;
    $entry->particulars = $particulars;
    $entry->tran_type = $type;
    if(isset($data->remarks)) $entry->remarks = $data->remarks;
    if(isset($data->entry_type)) $entry->entry_type = $data->entry_type;
    if(isset($data->entry_id)) $entry->entry_id = $data->entry_id;
    if(isset($data->url)) $entry->url = $data->url;
    if(isset($data->month)) $entry->month = $data->month;
    if(isset($data->hotel)) $entry->hotel = $data->hotel;
    if(isset($data->bank) && nn($data->bank)) $entry->bank = $data->bank;
    if(isset($data->expense_date)) $entry->expense_date = $data->expense_date;
    if(isset($data->payment_method)) $entry->payment_method = $data->payment_method;
    $entry->entry_by = uid();
    $entry->entry_time = now();
    $entry->accountpath = $accountpath;

    R::store($entry);
    return $entry;
}

function addLink($print = true)
{
    $link = '<div class="col-6 text-right right"><a href="' . mlink('add') . '" class="btn btn-primary" style="position">Add</a></div>';
    if ($print) {
        print $link;
    } else {
        return $link;
    }
}

function editDeleteLink($id, $print = false)
{
    return editLink($id, $print) . deleteLink($id, $print);
}

function editLink($id, $print = false)
{
    $link = "<a class='btn btn-primary btn-sm' href='" . mlink('edit/' . $id) . "'>Edit</a>";
    if ($print) {
        print $link;
    } else {
        return $link;
    }
}

function deleteLink($id, $print = false)
{
    $link = "<a class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='#delMoal' onclick='del($id)'>Delete</a>";
    if ($print) {
        print $link;
    } else {
        return $link;
    }
}
// function mlink($link)
// {
//     return PAGE . "/$link";
// }
function banner(){
    
}
function p_image($section, $default = '', $options = [])
{
    print ROOT . getContent($section, $default, 'image', $options);
}
function p_text($section, $default = '', $options = [])
{
    print getContent($section, $default, 'text', $options);
}
function p_largetext($section, $default = '', $options = [])
{
    print getContent($section, $default, 'largetext', $options);
}
function getContent($section, $default = '', $type = '', $options = [])
{
    $options = (object) $options;
    if (!h($options, 'page')) {
        $options->page = PAGE;
    }
    $content = R::findOne("content", "page=? AND section=? AND content_type=?", [$options->page, $section, $type]);
    if ($content == null && !empty($default)) {
        $content = R::dispense("content");
        $content->page = $options->page;
        $content->section = $section;
        $content->content = $default;
        $content->content_type = $type;
        R::store($content);
    }
    return $content->content;
}
function h($options, $key)
{
    return isset($options->$key) && !empty($options->$key);
}
function mkdir3($path)
{
    $dirs = explode("/", $path);
    $cur_path = "";
    foreach ($dirs as $dir) {
        $cur_path = $cur_path == "" ? "$dir" : "$cur_path/$dir";
        if (!file_exists($cur_path)) {
            mkdir($cur_path);
        }
    }
}

function ext2($filename)
{
    $filename = trim($filename);
    $ext = '';
    $len = 2;
    while (!strpos("a" . $ext, ".")) {
        $ext = substr($filename, strlen($filename) - $len++);
        if ($len > 12) {
            $ext = '';
            break;
        }
    }
    return $ext;
}

/*
    <div class="col-md-6">
      <div class="form-floating mb-0">
        <input type="email" class="form-control" id="floatingInput" placeholder="Email address" />
        <label for="floatingInput">Email address</label>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-floating mb-0">
        <input type="password" class="form-control" id="floatingPassword" placeholder="Password" />
        <label for="floatingPassword">Password</label>
      </div>
    </div>
*/

//form-floating mb-0
function buildForm($formItems = [], $_class='form-group')
{
    $form = "";
    foreach ($formItems as $key => $fi) {
        $class = isset($fi['class']) ? $fi['class'] : $_class;
        $required = !empty($fi['required']) ? " required" : "";
        $requiredFile = (!empty($fi['required']) && empty($fi['value'])) ? " required" : "";
        if ($fi['type'] == 'text') {
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    <input type='text' id='$key' class='form-control' name='$key' value='{$fi['value']}'{$required}>
                </div>
            </div>";
        } elseif ($fi['type'] == 'textarea') {
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    <textarea id='$key' type='text' class='form-control' name='$key'{$required}>{$fi['value']}</textarea>
                </div>
            </div>";
        } elseif ($fi['type'] == 'dropdown') {
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    ";
                    $form .= "<select name='$key' id='$key' class='form-control'{$required}>";
            if (isset($fi['table'])) {
                $valueField = isset($fi['valueField']) ? $fi['valueField'] : 'id';
                $textField = isset($fi['textField']) ? $fi['textField'] : 'name';
                $filter = isset($fi['filter']) ? $fi['filter'] : '';
                if($filter){
                    $opts = R::find($fi['table'], $filter);
                } else{
                    $opts = R::find($fi['table']);
                }
                foreach ($opts as $key => $op) {
                    $form .= "<option value='". $op->$valueField . "'";
                    if ($fi['value'] == $op->$valueField) {
                        $form .= " selected ";
                    }
                    $form .= ">{$op->$textField}</option>";
                }
            } else {
                foreach ($fi['options'] as $key => $op) {
                    $form .= "<option value='$op'";
                    if ($fi['value'] == $op) {
                        $form .= " selected ";
                    }
                    $form .= ">$op</option>";
                }
            }
            $form .= "</select>";
            $form .= "
                </div>
            </div>";
        } elseif ($fi['type'] == 'image') {
            $form .= "<div class='col-{$fi['col']}'>
                <div class='text-center'>
                    <label for='$key'>{$fi['label']}</label>
                    <input id='$key' type='file' accept='image/*' name='$key'{$requiredFile}>";
            if ($fi['value']) {
                $form .= "<img src='" . ROOT . "/{$fi['value']}' height='200px'>";
            }
            $form .= "</div>
            </div>";
        } elseif ($fi['type'] == 'buttons') {
            $form .= "<div class='col-{$fi['col']}'>";
            foreach($fi['options'] as $opt) $form .=  "<button class='$class' type='button'>{$opt}</button>";
            $form .= "</div>";
        } elseif ($fi['type'] == 'radios') {
            $form .= "<div class='col-{$fi['col']}'>";
            foreach($fi['options'] as $opt) $form .=  "<div class='radio-label'><input name='$key' class='$class' type='radio' value='$opt'{$required}><span>{$opt}</span></div>";
            $form .= "</div>";
        } elseif ($fi['type'] == 'radio') {
            $form .= "<div class='col-{$fi['col']}'>";
            foreach($fi['options'] as $opt) $form .=  "<input name='$key' class='$class' type='radio' value='$opt'{$required}>{$opt}".space(3);
            $form .= "</div>";
        } elseif ($fi['type'] == 'date2') {
            $form .= "<div class='col-{$fi['col']}'>
                <label for='$key'>{$fi['label']}</label><br>
                ".dateSelector($key)."
            </div>";
        } elseif($fi['type'] == 'html'){
            $form .= "<div class='col-{$fi['col']}'>{$fi['html']}</div>";
        } else{
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    <input type='{$fi['type']}' class='form-control' name='$key' id='$key' value='{$fi['value']}'{$required}>
                </div>
            </div>";
        }
    }
    return $form;
}


function nf2($number, $decimals = 2)
{
    // If the number is a whole number, don't display decimal places
    if (is_float($number) && floor($number) == $number) {
        return number_format($number, 0); // No decimals
    }
    return number_format($number, $decimals);
}
function dp($name='date', $date = false, $min = false){
    return "<input type='date' name='$name' class='form-control form-control-fluid dp w120' value='$date' ".($min? "min='$min'":"").">";
}
function mf($month){
    return date("M Y", strtotime("{$month}-01"));
}

function dp2($name='date', $date = false){
    return "<input type='date' name='$name' class='datepicker form-control form-control-fluid' value='$date'>";
}

function isUserIn($users = []){//return true;
    if(uid() == 1) return true;
    return in_array(strtolower(username()), $users);
    // return in_array('lemon', $users);
}

function isUserIn2($users = []){
    return in_array(username(), $users);
    // return in_array('lemon', $users);
}


function getAccountsWithChild($select = '', $parent = 0, $depth = 0, $group = false, $account_selection_only = false) {
    if(!$parent){
        $filter = "parent IS NULL";
    } else{
        $filter = "parent = $parent";
    }
    $options = "";
    $categories = select("*", "expense_account", $filter);
    if ($categories->num_rows) {
        while ($category = mysqli_fetch_object($categories)) {
            $subOptions = getAccountsWithChild($select, $category->id, $depth + 1, $group, $account_selection_only);
            if ($group && $subOptions != "") {
                $options .= "<optgroup label='" . space($depth * 3) . "$category->name' disabled></optgroup>" . $subOptions;
            } else {
                $options .= "<option value='$category->id' data-code='$category->fullcode'";
                if ($select == $category->id) {
                    $options .= " selected";
                }
                if($account_selection_only){
                    if($category->has_child){
                        $options .= " disabled";        
                    }
                }
                $options .= ">" . space($depth * 3) . "$category->name</option>" . $subOptions;
            }
        }
    }
    return $options;
}
