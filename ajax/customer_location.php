<?php
session_start();
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");

$customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($customerId <= 0) {
    echo '<div class="alert alert-danger">Invalid customer ID</div>';
    exit;
}

// Get customer information
$customer = R::load('customer', $customerId);

if (!$customer->id) {
    echo '<div class="alert alert-danger">Customer not found</div>';
    exit;
}

// Build location information
$locParts = [];
$locParts[] = $customer->company;
if (isset($customer->code) && nn($customer->code)) $locParts[] = "Code: $customer->code";
if (isset($customer->city) && nn($customer->city)) $locParts[] = "Area: $customer->city";
if (isset($customer->location) && nn($customer->location)) $locParts[] = "Location: $customer->location";
if (isset($customer->phone) && nn($customer->phone)) $locParts[] = "Phone: $customer->phone";
if (isset($customer->mobile) && nn($customer->mobile)) $locParts[] = "Mobile: $customer->mobile";

// Display the information
foreach ($locParts as $part) {
    echo '<div>' . htmlspecialchars($part) . '</div>';
}
?>
