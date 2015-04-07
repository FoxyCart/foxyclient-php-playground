<?php
require __DIR__ . '/bootstrap.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Example Requests for the Foxy Hypermedia API</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <style>
        body { padding-bottom: 70px; }
        footer { padding-top: 50px; }
    </style>
  </head>
  <body>

<nav class="navbar navbar-default">
  <div class="container">
    <a class="navbar-brand" href="/">Foxy hAPI Coupon Builder Example</a>
    <ul class="nav navbar-nav">
      <li><a href="/?action=">Home</a></li>
      <li><a target="_blank" href="https://api<?php print ($fc->getUseSandbox() ? '-sandbox' : ''); ?>.foxycart.com/hal-browser/browser.html">HAL Browser</a></li>
      <li><a href="/coupons.php?action=logout">Logout</a></li>
        <?php
        if (isset($_SESSION['store_name'])) {
            ?>
            <li class="divider"></li>
            <li class="navbar-text">STORE: <?php print $_SESSION['store_name']; ?></li>
            <?php
        }
        ?>
    </ul>
    <ul class="nav navbar-nav navbar-right">
       <li><p class="navbar-text"><?php print ($fc->getUseSandbox() ? '<span class="text-success">SANDBOX</span>' : '<span class="text-danger">PRODUCTION</span>'); ?></p></li>
    </ul>
  </div>
</nav>
    <div class="container">
<?php

// update our session/client if needed.
// NOTE: This example uses the session, but you could also be using a database or some other persistance layer.
if (isset($_SESSION['access_token']) && $fc->getAccessToken() != $_SESSION['access_token']) {
    if ($fc->getAccessToken() == '') {
        $fc->setAccessToken($_SESSION['access_token']);
    }
}
if (isset($_SESSION['refresh_token']) && $fc->getRefreshToken() != $_SESSION['refresh_token']) {
    if ($fc->getRefreshToken() == '') {
        $fc->setRefreshToken($_SESSION['refresh_token']);
    }
}
if (isset($_SESSION['client_id']) && $fc->getClientId() != $_SESSION['client_id']) {
    if ($fc->getClientId() == '') {
        $fc->setClientId($_SESSION['client_id']);
    }
}
if (isset($_SESSION['client_secret']) && $fc->getClientSecret() != $_SESSION['client_secret']) {
    if ($fc->getClientSecret() == '') {
        $fc->setClientSecret($_SESSION['client_secret']);
    }
}
if (isset($_SESSION['access_token_expires']) && $fc->getAccessTokenExpires() != $_SESSION['access_token_expires']) {
    if ($fc->getAccessTokenExpires() == '') {
        $fc->setAccessTokenExpires($_SESSION['access_token_expires']);
    }
}

// BEGIN HERE
if ($action == '') {
    ?>
    <h1>Foxy Coupon Builder</h1>
    <p>
        Please check out the <a href="https://api-sandbox.foxycart.com/docs">Foxy hAPI documentation</a> to better understand this example.
    </p>
    <p>
        This exmaple will walk through using FoxyClient.php to:
        <ol>
            <li><a href="/coupons.php?action=view_coupons">View Coupons</a></li>
            <li>View Coupon (view coupons first)</li>
            <li>Edit Coupon (view coupons first)</li>
            <li>Delete Coupon (view coupons first)</li>
            <li><a href="/coupons.php?action=add_coupon_form">Add Coupon</a></li>
        </ol>
    </p>
    <?php
} else {
    if ($action == 'authorization_code_grant') {
        $result = $fc->getAccessTokenFromAuthorizationCode($_GET['code']);
        $errors = array_merge($errors,$fc->getErrors($result));
        if (!count($errors)) {
            $_SESSION['access_token'] = $result['access_token'];
            $_SESSION['access_token_expires'] = time() + $result['expires_in'];
            $_SESSION['refresh_token'] = $result['refresh_token'];
            $action = "view_coupons";
            /*
            ?>
            <h3 class="alert alert-success" role="alert">Access Token Obtained</h3>
            <h3>Result:</h3>
            <pre><?php print_r($result); ?></pre>
            <?php
            */
        }
    } else {
        // check authentication
        if ($fc->getClientId() == '' && $fc->getClientSecret() == '') {
            die("You must have a client id and client secret to use this tool.");
        } else {
            if ($fc->getAccessToken() == '' && $fc->getRefreshToken() == '') {
                $authorizaiton_url = $fc->getAuthorizationEndpoint() . '?client_id=' . $fc->getClientId();
                $authorizaiton_url .= '&scope=store_full_access&state=' . $token;
                $authorizaiton_url .= '&response_type=code';
                header("location: " . $authorizaiton_url);
                die();
            }
        }
    }
}

// Bookmark some URIs for this session
if ($action == 'view_coupons' || $action == 'add_coupon') {
    $errors = array();
    if (!isset($_SESSION['store_uri']) || $_SESSION['store_uri'] == ''
        || !isset($_SESSION['store_name']) || $_SESSION['store_name'] == '') {
        $result = $fc->get();
        $errors = array_merge($errors,$fc->getErrors($result));
        $store_uri = $fc->getLink('fx:store');
        if ($store_uri == '') {
            $errors[] = 'Unable to obtain fx:store href';
        } else {
            $_SESSION['store_uri'] = $store_uri;
            $result = $fc->get($store_uri);
            $errors = array_merge($errors,$fc->getErrors($result));
            if (!count($errors)) {
                $_SESSION['store_name'] = $result['store_name'];
            }
        }
    }
    if (isset($_SESSION['store_uri']) && (!isset($_SESSION['coupons_uri']) || $_SESSION['coupons_uri'] == '')) {
        $result = $fc->get($_SESSION['store_uri']);
        $errors = array_merge($errors,$fc->getErrors($result));
        $coupons_uri = $fc->getLink('fx:coupons');
        if ($coupons_uri == '') {
            $errors[] = 'Unable to obtain fx:coupons href';
        } else {
            $_SESSION['coupons_uri'] = $coupons_uri;
        }
    }
    if (count($errors)) {
        $action = 'edit_coupon_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'delete_coupon') {
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $result = $fc->delete($_REQUEST['resource_uri']);
        $errors = array_merge($errors,$fc->getErrors($result));
        print '<h3 class="alert alert-success" role="alert">Coupon Deleted</h3>';
        $action = 'view_coupons';
    }
    if (count($errors)) {
        $action = 'edit_coupon_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'delete_coupon_form') {
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
            ?>
            <p>Are you sure you want to delete the <code><?php print $_REQUEST['resource_name']; ?></code> coupon?
            <form role="form" action="/coupons.php?action=delete_coupon" method="post" class="form-horizontal">
            <input type="hidden" name="resource_uri" value="<?php print htmlspecialchars($_REQUEST['resource_uri'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="submit" name="submit" class="btn btn-danger" value="Yes, Delete It" />
            </form>
            <?php
    }
    if (count($errors)) {
        $action = 'view_coupon';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}


if ($action == 'add_coupon') {
    $errors = array();
    $data = array(
        'name' => $_POST['name'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'number_of_uses_allowed' => $_POST['number_of_uses_allowed'],
        'number_of_uses_allowed_per_customer' => $_POST['number_of_uses_allowed_per_customer'],
        'number_of_uses_allowed_per_code' => $_POST['number_of_uses_allowed_per_code'],
        'product_code_restrictions' => $_POST['product_code_restrictions'],
        'coupon_discount_type' => $_POST['coupon_discount_type'],
        'coupon_discount_details' => $_POST['coupon_discount_details'],
        'combinable' => $_POST['combinable'],
        'multiple_codes_allowed' => $_POST['multiple_codes_allowed'],
        'exclude_category_discounts' => $_POST['exclude_category_discounts'],
        'exclude_line_item_discounts' => $_POST['exclude_line_item_discounts'],
        'is_taxable' => $_POST['is_taxable'],
    );

    // TODO: rmove this once we push out https://github.com/FoxyCart/hypermedia-api/pull/119
    if ($data['start_date'] == '') {
        unset($data['start_date']);
    }
    if ($data['end_date'] == '') {
        unset($data['end_date']);
    }

    // TODO: rmove this once we push out https://github.com/FoxyCart/hypermedia-api/pull/120
    if ($data['combinable'] == 'true') {
        $data['combinable'] = true;
    }
    if ($data['multiple_codes_allowed'] == 'true') {
        $data['multiple_codes_allowed'] = true;
    }
    if ($data['exclude_category_discounts'] == 'true') {
        $data['exclude_category_discounts'] = true;
    }
    if ($data['exclude_line_item_discounts'] == 'true') {
        $data['exclude_line_item_discounts'] = true;
    }
    if ($data['is_taxable'] == 'true') {
        $data['is_taxable'] = true;
    }

/*
    print "<pre>";
    var_dump($data);
    var_dump(http_build_query($data));
    print "</pre>";
*/
    $result = $fc->post($_SESSION['coupons_uri'],$data);
    $errors = array_merge($errors,$fc->getErrors($result));
    if (!count($errors)) {
        print '<div class="alert alert-success" role="alert">';
        print $result['message'];
        print '</div>';
        $_REQUEST['resource_uri'] = $result['_links']['self']['href'];
        $action = 'view_coupon';
    }
    if (count($errors)) {
        $action = 'add_coupon_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'add_coupon_form') {
    ?>
    <h2>Add Coupon</h2>
    <form role="form" action="/coupons.php?action=add_coupon" method="post" class="form-horizontal">
        <div class="form-group">
            <label for="name" class="col-sm-2 control-label">Coupon Name<span class="text-danger">*</span></label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="name" name="name" maxlength="200" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ""; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="start_date" class="col-sm-2 control-label">Start Date</label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="start_date" name="start_date" maxlength="200" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ""; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="end_date" class="col-sm-2 control-label">End Date</label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="end_date" name="end_date" maxlength="200" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ""; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="number_of_uses_allowed" class="col-sm-2 control-label">Number of Uses Allowed</label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="number_of_uses_allowed" name="number_of_uses_allowed" maxlength="200" value="<?php echo isset($_POST['number_of_uses_allowed']) ? htmlspecialchars($_POST['number_of_uses_allowed']) : 0; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="number_of_uses_allowed_per_customer" class="col-sm-2 control-label">Number of Uses Allowed Per Customer</label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="number_of_uses_allowed_per_customer" name="number_of_uses_allowed_per_customer" maxlength="200" value="<?php echo isset($_POST['number_of_uses_allowed_per_customer']) ? htmlspecialchars($_POST['number_of_uses_allowed_per_customer']) : 0; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="number_of_uses_allowed_per_code" class="col-sm-2 control-label">Number of Uses Allowed Per Code</label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="number_of_uses_allowed_per_code" name="number_of_uses_allowed_per_code" maxlength="200" value="<?php echo isset($_POST['number_of_uses_allowed_per_code']) ? htmlspecialchars($_POST['number_of_uses_allowed_per_code']) : 0; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="product_code_restrictions" class="col-sm-2 control-label">Product Code Restrictions</label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="product_code_restrictions" name="product_code_restrictions" maxlength="200" value="<?php echo isset($_POST['product_code_restrictions']) ? htmlspecialchars($_POST['product_code_restrictions']) : ""; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="coupon_discount_type" class="col-sm-2 control-label">Coupon Discount Type</label>
            <div class="col-sm-3">
                <select name="coupon_discount_type">
                <?php $selected = (isset($_POST['coupon_discount_type']) && $_POST['coupon_discount_type'] == 'quantity_amount') ? ' selected="selected"' : ''; ?>
                <option<?php print $selected; ?> value="quantity_amount">Amount Based on Quantity (quantity_amount)</option>
                <?php $selected = (isset($_POST['coupon_discount_type']) && $_POST['coupon_discount_type'] == 'quantity_percentage') ? ' selected="selected"' : ''; ?>
                <option<?php print $selected; ?> value="quantity_percentage">Percentage Based on Quantity (quantity_percentage)</option>
                <?php $selected = (isset($_POST['coupon_discount_type']) && $_POST['coupon_discount_type'] == 'price_amount') ? ' selected="selected"' : ''; ?>
                <option<?php print $selected; ?> value="price_amount">Amount Based on Price (price_amount)</option>
                <?php $selected = (isset($_POST['coupon_discount_type']) && $_POST['coupon_discount_type'] == 'price_percentage') ? ' selected="selected"' : ''; ?>
                <option<?php print $selected; ?> value="price_percentage">Percentage Based on Price (price_percentage)</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="coupon_discount_details" class="col-sm-2 control-label">Coupon Discount Details<span class="text-danger">*</span></label>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="coupon_discount_details" name="coupon_discount_details" maxlength="200" value="<?php echo isset($_POST['coupon_discount_details']) ? htmlspecialchars($_POST['coupon_discount_details']) : ""; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="combinable_yes" class="col-sm-2 control-label">Is Combinable?</label>
            <div class="col-sm-3">
                <label class="radio-inline">
                    <?php $checked = (isset($_POST['combinable']) && $_POST['combinable'] == 'true') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="combinable_true" name="combinable" value="true" /> Yes
                </label>
                <label class="radio-inline">
                    <?php $checked = (!isset($_POST['combinable']) || $_POST['combinable'] == 'false') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="combinable_false" name="combinable" value="false" /> No
                </label>
            </div>
        </div>
        <div class="form-group">
            <label for="multiple_codes_allowed_yes" class="col-sm-2 control-label">Multiple Codes Allowed?</label>
            <div class="col-sm-3">
                <label class="radio-inline">
                    <?php $checked = (isset($_POST['multiple_codes_allowed']) && $_POST['multiple_codes_allowed'] == 'true') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="multiple_codes_allowed_true" name="multiple_codes_allowed" value="true" /> Yes
                </label>
                <label class="radio-inline">
                    <?php $checked = (!isset($_POST['multiple_codes_allowed']) || $_POST['multiple_codes_allowed'] == 'false') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="multiple_codes_allowed_false" name="multiple_codes_allowed" value="false" /> No
                </label>
            </div>
        </div>
        <div class="form-group">
            <label for="exclude_category_discounts_yes" class="col-sm-2 control-label">Exclude Category Discounts?</label>
            <div class="col-sm-3">
                <label class="radio-inline">
                    <?php $checked = (isset($_POST['exclude_category_discounts']) && $_POST['exclude_category_discounts'] == 'true') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="exclude_category_discounts_true" name="exclude_category_discounts" value="true" /> Yes
                </label>
                <label class="radio-inline">
                    <?php $checked = (!isset($_POST['exclude_category_discounts']) || $_POST['exclude_category_discounts'] == 'false') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="exclude_category_discounts_false" name="exclude_category_discounts" value="false" /> No
                </label>
            </div>
        </div>
        <div class="form-group">
            <label for="exclude_line_item_discounts_yes" class="col-sm-2 control-label">Exclude Line Item Discounts?</label>
            <div class="col-sm-3">
                <label class="radio-inline">
                    <?php $checked = (isset($_POST['exclude_line_item_discounts']) && $_POST['exclude_line_item_discounts'] == 'true') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="exclude_line_item_discounts_true" name="exclude_line_item_discounts" value="true" /> Yes
                </label>
                <label class="radio-inline">
                    <?php $checked = (!isset($_POST['exclude_line_item_discounts']) || $_POST['exclude_line_item_discounts'] == 'false') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="exclude_line_item_discounts_false" name="exclude_line_item_discounts" value="false" /> No
                </label>
            </div>
        </div>
        <div class="form-group">
            <label for="is_taxable_yes" class="col-sm-2 control-label">Is Taxable?</label>
            <div class="col-sm-3">
                <label class="radio-inline">
                    <?php $checked = (isset($_POST['is_taxable']) && $_POST['is_taxable'] == 'true') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="is_taxable_true" name="is_taxable" value="true" /> Yes
                </label>
                <label class="radio-inline">
                    <?php $checked = (!isset($_POST['is_taxable']) || $_POST['is_taxable'] == 'false') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="radio" class="form-control" id="is_taxable_false" name="is_taxable" value="false" /> No
                </label>
            </div>
        </div>
        <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
        <button type="submit" class="btn btn-primary">Add Coupon</button>
    </form>
    <?php
}

if ($action == 'save_coupon') {
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $data = array(
            'name' => $_POST['name'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'number_of_uses_allowed' => $_POST['number_of_uses_allowed'],
            'number_of_uses_to_date' => $_POST['number_of_uses_to_date'],
            'number_of_uses_allowed_per_customer' => $_POST['number_of_uses_allowed_per_customer'],
            'number_of_uses_allowed_per_code' => $_POST['number_of_uses_allowed_per_code'],
            'product_code_restrictions' => $_POST['product_code_restrictions'],
            'coupon_discount_type' => $_POST['coupon_discount_type'],
            'coupon_discount_details' => $_POST['coupon_discount_details'],
            'combinable' => $_POST['combinable'],
            'multiple_codes_allowed' => $_POST['multiple_codes_allowed'],
            'exclude_category_discounts' => $_POST['exclude_category_discounts'],
            'exclude_line_item_discounts' => $_POST['exclude_line_item_discounts'],
            'is_taxable' => $_POST['is_taxable'],
        );

        // TODO: rmove this once we push out https://github.com/FoxyCart/hypermedia-api/pull/120
        if ($data['combinable'] == 'true') {
            $data['combinable'] = 1;
        }
        if ($data['multiple_codes_allowed'] == 'true') {
            $data['multiple_codes_allowed'] = 1;
        }
        if ($data['exclude_category_discounts'] == 'true') {
            $data['exclude_category_discounts'] = 1;
        }
        if ($data['exclude_line_item_discounts'] == 'true') {
            $data['exclude_line_item_discounts'] = 1;
        }
        if ($data['is_taxable'] == 'true') {
            $data['is_taxable'] = 1;
        }
        /*
        print "<pre>";
        var_dump($data);
        var_dump(http_build_query($data));
        print "</pre>";
        */


        $result = $fc->patch($_REQUEST['resource_uri'],$data);
        $errors = array_merge($errors,$fc->getErrors($result));
        if (!count($errors)) {
            $action = 'view_coupon';
        }
    }
    if (count($errors)) {
        $action = 'edit_coupon_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'edit_coupon_form') {
    ?>
    <h2>Edit Coupon</h2>
    <?php
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $result = $fc->get($_REQUEST['resource_uri']);
        $errors = array_merge($errors,$fc->getErrors($result));
        if (!count($errors)) {
            ?>
            <form role="form" action="/coupons.php?action=save_coupon" method="post" class="form-horizontal">
            <?php
            $boolean_fields = array('combinable','multiple_codes_allowed','exclude_category_discounts','exclude_line_item_discounts','is_taxable');
            foreach($result as $field => $value) {
                if ($field != '_links' && $field != 'date_created' && $field != 'date_modified') {
                    if ($field == 'coupon_discount_type') {
                        ?>
                        <div class="form-group">
                            <label for="coupon_discount_type" class="col-sm-2 control-label">Coupon Discount Type</label>
                            <div class="col-sm-3">
                                <select name="coupon_discount_type">
                                <?php $selected = ($value == 'quantity_amount') ? ' selected="selected"' : ''; ?>
                                <option<?php print $selected; ?> value="quantity_amount">Amount Based on Quantity (quantity_amount)</option>
                                <?php $selected = ($value == 'quantity_percentage') ? ' selected="selected"' : ''; ?>
                                <option<?php print $selected; ?> value="quantity_percentage">Percentage Based on Quantity (quantity_percentage)</option>
                                <?php $selected = ($value == 'price_amount') ? ' selected="selected"' : ''; ?>
                                <option<?php print $selected; ?> value="price_amount">Amount Based on Price (price_amount)</option>
                                <?php $selected = ($value == 'price_percentage') ? ' selected="selected"' : ''; ?>
                                <option<?php print $selected; ?> value="price_percentage">Percentage Based on Price (price_percentage)</option>
                                </select>
                            </div>
                        </div>
                        <?php
                    } elseif (in_array($field, $boolean_fields)) {
                        ?>
                        <div class="form-group">
                            <label for="combinable_yes" class="col-sm-2 control-label"><?php print ucwords(str_replace('_',' ',$field)); ?>?</label>
                            <div class="col-sm-3">
                                <label class="radio-inline">
                                    <?php $checked = ($value) ? ' checked="checked"' : ''; ?>
                                    <input<?php print $checked; ?> type="radio" class="form-control" id="<?php print $field; ?>_true" name="<?php print $field; ?>" value="true" /> Yes
                                </label>
                                <label class="radio-inline">
                                    <?php $checked = (!$value) ? ' checked="checked"' : ''; ?>
                                    <input<?php print $checked; ?> type="radio" class="form-control" id="<?php print $field; ?>_false" name="<?php print $field; ?>" value="false" /> No
                                </label>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="form-group">
                            <label for="<?php print $field; ?>" class="col-sm-2 control-label"><?php print ucwords(str_replace('_',' ',$field)); ?></label>
                            <div class="col-sm-3">
                                <input type="<?php print $field; ?>"
                                    class="form-control"
                                    id="<?php print $field; ?>"
                                    name="<?php print $field; ?>"
                                    maxlength="200"
                                    value="<?php print htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>"
                                />
                            </div>
                        </div>
                        <?php
                    }
                }
            }
            ?>
                <input type="hidden" name="resource_uri" value="<?php print htmlspecialchars($_REQUEST['resource_uri'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
                <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
                <button type="submit" class="btn btn-primary">Save Coupon</button>
            </form>
            <?php
        }
   }
    if (count($errors)) {
        $action = '';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'view_coupon') {
    ?>
    <h2>View Coupon</h2>
    <?php
    $errors = array();
    $resouce_uri = (isset($_REQUEST['resource_uri']) ? $_REQUEST['resource_uri'] : '');
    if ($resouce_uri == '') {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $result = $fc->get($resouce_uri,array('zoom' => 'coupon_codes'));
        $errors = array_merge($errors,$fc->getErrors($result));
        if (!count($errors)) {
            ?>
            <h3><?php print $result['name']; ?></h3>
            <div class="col-md-6">
            <table class="table">
            <?php
            $embedded_data = array();
            $boolean_fields = array('combinable','multiple_codes_allowed','exclude_category_discounts','exclude_line_item_discounts','is_taxable');
            foreach($result as $field => $value) {
                if ($field != '_links' && $field != '_embedded' && $field != 'name') {
                    if (in_array($field, $boolean_fields)) {
                        $value = ($value) ? 'yes' : 'no';
                    }
                    ?>
                    <tr>
                        <td><?php print ucwords(str_replace('_',' ',$field)); ?>: </td>
                        <td><?php print htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></td>
                    </tr>
                    <?php
                }
                if ($field == '_embedded') {
                    $embedded_data = $value['fx:coupon_codes'];
                }
            }
            if (count($embedded_data)) {
                ?>
                <tr><td colspan="2">
                <h2>Coupon Codes</h2>
                <?php
/*
print "<pre>";
var_dump($embedded_data);
print "</pre>";
*/
                foreach($embedded_data as $coupon_code) {
                    foreach($coupon_code as $cc_field => $cc_value) {
                        if ($cc_field == 'code') {
                            print '<code>' . $cc_value . '</code>';
                        }
                        if ($cc_field == 'number_of_uses_to_date') {
                            print ' (' . $cc_value . ' uses so far)<br />';
                        }
                    }
                }
                ?>
                </td>
                </tr>
                <?php
            }
            ?>
            </table>
            <form role="form" action="/coupons.php?action=edit_coupon_form" method="post" class="form-horizontal">
            <input type="hidden" name="resource_uri" value="<?php print htmlspecialchars($resouce_uri, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="submit" name="submit" class="btn btn-warning" value="Edit <?php print $result['name']; ?>" />
            </form><br />
            <a class="btn btn-primary" href="/coupons.php?action=view_coupons">View All Coupons</a>
            </div>
            <?php
        }
   }
    if (count($errors)) {
        $action = '';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'view_coupons') {
    ?>
    <h2>View Coupons</h2>
    <?php
    $errors = array();
    $coupons_uri = $_SESSION['coupons_uri'];
    if (isset($_REQUEST['coupons_uri'])) {
        $coupons_uri = $_REQUEST['coupons_uri'];
    }
    $result = $fc->get($coupons_uri, array("limit" => 5));
    $errors = array_merge($errors,$fc->getErrors($result));
    if (!count($errors)) {
        ?>
        <h3>Coupons for <?php print $_SESSION['store_name']; ?></h3>
        <?php
        print '<p>Displaying ' . $result['returned_items'] . ' (' . ($result['offset']+1) . ' through ' . min($result['total_items'],($result['limit']+$result['offset'])) . ') of ' . $result['total_items'] . ' total coupons.</p>'
        ?>
        <nav>
          <ul class="pagination">
            <li>
              <a href="/coupons.php?action=view_coupons&amp;coupons_uri=<?php print urlencode($result['_links']['prev']['href']); ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>
            <li>
              <a href="/coupons.php?action=view_coupons&amp;coupons_uri=<?php print urlencode($result['_links']['next']['href']); ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
              </a>
            </li>
          </ul>
        </nav>
        <table class="table">
        <tr>
            <th>Coupon Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Uses So Far</th>
            <th>Uses Allowed</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
        <?php
        foreach($result['_embedded']['fx:coupons'] as $coupon) {
            ?>
            <tr>
                <td><?php print $coupon['name']; ?></td>
                <td><?php print $coupon['start_date']; ?></td>
                <td><?php print $coupon['end_date']; ?></td>
                <td><?php print $coupon['number_of_uses_to_date']; ?></td>
                <td><?php print $coupon['number_of_uses_allowed']; ?></td>
                <td><a class="btn btn-primary" href="/coupons.php?action=view_coupon&amp;resource_uri=<?php print urlencode($coupon['_links']['self']['href']); ?>">View</a></td>
                <td><a class="btn btn-warning" href="/coupons.php?action=edit_coupon_form&amp;resource_uri=<?php print urlencode($coupon['_links']['self']['href']); ?>">Edit</a></td>
                <td><a class="btn btn-danger" href="/coupons.php?action=delete_coupon_form&amp;resource_uri=<?php print urlencode($coupon['_links']['self']['href']); ?>&amp;resource_name=<?php print urlencode($coupon['name']); ?>">Delete</a></td>
            </tr>
            <?php
        }
        ?>
        </table>
        <a class="btn btn-primary" href="/coupons.php?action=add_coupon_form">Add Coupon</a>
        <?php
    }
    if (count($errors)) {
        $action = '';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'logout') {
    session_destroy();
    $fc->clearCredentials();
    print '<h2>You are Logged out</h2>';
    print '<br /><a href="/coupons.php?action=">Home</a>';
}


// NOTE: This example uses the session, but you could also be using a database or some other persistance layer.
if (isset($_SESSION['access_token']) && $fc->getAccessToken() != $_SESSION['access_token']) {
    // This can happen after a token refresh.
    if ($fc->getAccessToken() != '') {
        $_SESSION['access_token'] = $fc->getAccessToken();
    }
}
if (isset($_SESSION['access_token_expires']) && $fc->getAccessTokenExpires() != $_SESSION['access_token_expires']) {
    // This can happen after a token refresh.
    if ($fc->getAccessTokenExpires() != '') {
        $_SESSION['access_token_expires'] = $fc->getAccessTokenExpires();
    }
}
/*
if ($action != 'logout' && $fc->getAccessToken() != '') {
    print '<footer class="text-muted">Authenticated: ';
    print '<ul>';
    print '<li>client_id: ' . $fc->getClientId() . '</li>';
    print '<li>client_secret: (view source) <!--' . $fc->getClientSecret() . '--></li>';
    print '<li>access_token: ' . $fc->getAccessToken() . '</li>';
    print '<li>refresh_token: (view source) <!--' . $fc->getRefreshToken() . '--></li>';
    if ($fc->getAccessTokenExpires() != '') {
        print '<li>access_token_expires: ' . $fc->getAccessTokenExpires() . '</li>';
        print '<li>now: ' . time() . '</li>';
        print '<li>next token refresh: ' . ($fc->getAccessTokenExpires() - time()) . '</li>';
    }
    print '</ul>';
    print '</footer>';
}
*/
?>
</div>
</body>
</html>