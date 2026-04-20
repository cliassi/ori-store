<?php
require_once "f.inc.php";
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
        if ($fi['type'] == 'text') {
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    <input type='text' id='$key' class='form-control' name='$key' value='{$fi['value']}'>
                </div>
            </div>";
        } elseif ($fi['type'] == 'textarea') {
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    <textarea id='$key' type='text' class='form-control' name='$key'>{$fi['value']}</textarea>
                </div>
            </div>";
        } elseif ($fi['type'] == 'dropdown') {
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    ";
                    $form .= "<select name='$key' id='$key' class='form-control'>";
            if (isset($fi['table'])) {
                $valueField = isset($fi['valueField']) ? $fi['valueField'] : 'id';
                $textField = isset($fi['textField']) ? $fi['textField'] : 'name';
                $opts = R::find($fi['table']);
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
                    <label>{$fi['label']}</label><br>
                    <button type='button' id='pickImageBtn' class='btn btn-outline-primary mb-3'>Choose Photo</button>
                    <input id='$key' type='file' accept='image/*' name='$key' style='display:none;'>";
            if ($fi['value']) {
                $form .= "<br><img src='" . ROOT . "/{$fi['value']}' height='200px' style='margin-top:10px;'>";
            }
            $form .= "</div>
            </div>";
        } elseif ($fi['type'] == 'buttons') {
            $form .= "<div class='col-{$fi['col']}'>";
            foreach($fi['options'] as $opt) $form .=  "<button class='$class' type='button'>{$opt}</button>";
            $form .= "</div>";
        } elseif ($fi['type'] == 'radios') {
            $form .= "<div class='col-{$fi['col']}'>";
            foreach($fi['options'] as $opt) $form .=  "<div class='radio-label'><input name='$key' class='$class' type='radio' value='$opt'><span>{$opt}</span></div>";
            $form .= "</div>";
        } elseif ($fi['type'] == 'radio') {
            $form .= "<div class='col-{$fi['col']}'>";
            foreach($fi['options'] as $opt) $form .=  "<input name='$key' class='$class' type='radio' value='$opt'>{$opt}".space(3);
            $form .= "</div>";
        } elseif ($fi['type'] == 'date2') {
            $form .= "<div class='col-{$fi['col']}'>
                <label for='$key'>{$fi['label']}</label><br>
                ".dateSelector($key)."
            </div>";
        } else{
            $form .= "<div class='col-{$fi['col']}'>
                <div class='$class'>
                    <label for='$key'>{$fi['label']}</label>
                    <input type='{$fi['type']}' class='form-control' name='$key' id='$key' value='{$fi['value']}'>
                </div>
            </div>";
        }
    }
    return $form;
}


function nf2($amount){
    // if($amount)
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

function isUserIn($users = []){return true;
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
function getImageOrPlaceholder($imagePath, $productName = '')
    {
        $imagePath = "http://43.216.235.55/store/{$imagePath}";

        return str_replace('http://43.216.235.55/store/http://43.216.235.55/store/uploads/', 'http://43.216.235.55/store/uploads/', $imagePath);
        if (file_exists($imagePath)) {
            return $imagePath;
        }
        // Return a placeholder image URL or data URI
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
                <rect width="100" height="100" fill="#e5e7eb"/>
                <text x="50" y="45" font-family="Arial" font-size="10" fill="#6b7280" text-anchor="middle">No Image</text>
                <text x="50" y="60" font-family="Arial" font-size="8" fill="#9ca3af" text-anchor="middle">' . htmlspecialchars(substr($productName, 0, 12)) . '</text>
            </svg>
        ');
    }
