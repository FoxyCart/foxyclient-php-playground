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
    <a class="navbar-brand" href="/">Foxy hAPI Item Category Management Example</a>
    <ul class="nav navbar-nav">
      <li><a href="/item_categories.php?action=">Home</a></li>
      <li><a target="_blank" href="https://api<?php print ($fc->getUseSandbox() ? '-sandbox' : ''); ?>.foxycart.com/hal-browser/browser.html">HAL Browser</a></li>
      <li><a href="/item_categories.php?action=logout">Logout</a></li>
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
    <h1>Foxy Item Category Management</h1>
    <p>
        Please check out the <a href="https://api-sandbox.foxycart.com/docs">Foxy hAPI documentation</a> to better understand this example.
    </p>
    <p>
        This exmaple will walk through using FoxyClient.php to:
        <ol>
            <li><a href="/item_categories.php?action=view_item_categories">View item categories</a></li>
            <li>View Item Category (view item categories first)</li>
            <li>Edit Item Category (view item categories first)</li>
            <li>Delete Item Category (view item categories first)</li>
            <li><a href="/item_categories.php?action=add_item_category_form">Add Item Category</a></li>
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
            $action = "view_item_categories";
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
if ($action == 'view_item_categories' || $action == 'add_item_category') {
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
                $_SESSION['item_categories_uri'] = $result['_links']['fx:item_categories']['href'];
            }
        }
    }
    if (isset($_SESSION['store_uri']) && (!isset($_SESSION['item_categories_uri']) || $_SESSION['item_categories_uri'] == '')) {
        $result = $fc->get($_SESSION['store_uri']);
        $errors = array_merge($errors,$fc->getErrors($result));
        $item_categories_uri = $fc->getLink('fx:item_categories');
        if ($item_categories_uri == '') {
            $errors[] = 'Unable to obtain fx:item_categories href';
        } else {
            $_SESSION['item_categories_uri'] = $item_categories_uri;
        }
    }
    if (count($errors)) {
        $action = 'edit_item_category_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}
if ($action == 'delete_item_category') {
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $result = $fc->delete($_REQUEST['resource_uri']);
        $errors = array_merge($errors,$fc->getErrors($result));
        $action = 'view_item_categories';
    }
    if (count($errors)) {
        $action = 'edit_item_category_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    } else {
        print '<h3 class="alert alert-success" role="alert">item_category Deleted</h3>';
    }
}

if ($action == 'delete_item_category_form') {
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
            ?>
            <p>Are you sure you want to delete the <code><?php print $_REQUEST['resource_name']; ?></code> item category?
            <form role="form" action="/item_categories.php?action=delete_item_category" method="post" class="form-horizontal">
            <input type="hidden" name="resource_uri" value="<?php print htmlspecialchars($_REQUEST['resource_uri'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="submit" name="submit" class="btn btn-danger" value="Yes, Delete It" />
            </form>
            <?php
    }
    if (count($errors)) {
        $action = 'view_item_category';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'add_item_category') {
    $errors = array();
    $data = $_POST;
    unset($data['csrf_token']);
    $result = $fc->post($_SESSION['item_categories_uri'],$data);
    $errors = array_merge($errors,$fc->getErrors($result));
    if (!count($errors)) {
        print '<div class="alert alert-success" role="alert">';
        print $result['message'];
        print '</div>';
        $_REQUEST['resource_uri'] = $result['_links']['self']['href'];
        $action = 'view_item_category';
    }
    if (count($errors)) {
        $action = 'add_item_category_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'add_item_category_form') {
    // get the Siren Action for creating a category
    $errors = array();
    $fc->setAcceptContentType('application/vnd.siren+json');
    $result = $fc->get($_SESSION['item_categories_uri']);

    foreach($result['actions'] as $action) {
        if ($action['title'] == 'Create Item Category') {
            $create_action = $action;
        }
    }

    ?>
    <h2>Add Item Category</h2>
    <form role="form" action="/item_categories.php?action=add_item_category" method="post" class="form-horizontal">
        <?php
        foreach($create_action['fields'] as $field) {
            ?>
            <div class="form-group">
                <label for="<?php print $field['name']; ?>" class="col-sm-2 control-label"><?php print $field['title']; ?></label>
                <div class="col-sm-3">
            <?php
            if ($field['type'] == 'checkbox') {
            ?>
                <?php $checked = (isset($_POST[$field['name']]) && $_POST[$field['name']] == 'true') ? ' checked="checked"' : ''; ?>
                <input<?php print $checked; ?> type="checkbox" id="<?php print $field['name']; ?>" name="<?php print $field['name']; ?>" value="true" />
            <?php
            }
            if ($field['type'] == 'text' || $field['type'] == 'number' || $field['type'] == 'url' || $field['type'] == 'email') {
            ?>
                <input type="text" class="form-control" id="<?php print $field['name']; ?>" name="<?php print $field['name']; ?>" maxlength="200" value="<?php echo isset($_POST[$field['name']]) ? htmlspecialchars($_POST[$field['name']]) : ""; ?>">
            <?php
            }
            if ($field['type'] == 'radio') {
            ?>
                <select name="<?php print $field['name']; ?>" id="<?php print $field['name']; ?>">
                <?php
                foreach($field['options'] as $option_value => $option) {
                    $selected = (isset($_POST[$field['name']]) && $_POST[$field['name']] == $option_value) ? ' selected="selected"' : '';
                    ?>
                    <option<?php print $selected; ?> value="<?php print $option_value; ?>"><?php print $option; ?></option>
                    <?php
                }
                ?>
                </select>
                <?php
            }
            ?>
                </div>
            </div>
            <?php
        }
        ?>
        <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
        <button type="submit" class="btn btn-primary">Add Item Category</button>
    </form>
    <?php
}

if ($action == 'save_item_category') {
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $data = $_POST;
        unset($data['csrf_token']);
        unset($data['resource_uri']);
        $result = $fc->patch($_REQUEST['resource_uri'],$data);
        $errors = array_merge($errors,$fc->getErrors($result));
        if (!count($errors)) {
            print '<div class="alert alert-success" role="alert">';
            print "Item Category Saved!";
            print '</div>';
            $_REQUEST['resource_uri'] = $result['_links']['self']['href'];
            $action = 'view_item_category';
        }
    }
    if (count($errors)) {
        $action = 'edit_item_category_form';
        print '<div class="alert alert-danger" role="alert">';
        print '<h2>Error:</h2>';
        foreach($errors as $error) {
            print $error . '<br />';
        }
        print '</div>';
    }
}

if ($action == 'edit_item_category_form') {
    ?>
    <h2>Edit item_category</h2>
    <?php
    $errors = array();
    if (!isset($_REQUEST['resource_uri'])) {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $fc->setAcceptContentType('application/vnd.siren+json');
        $result = $fc->get($_REQUEST['resource_uri']);
        $errors = array_merge($errors,$fc->getErrors($result));
        if (!count($errors)) {
            foreach($result['actions'] as $action) {
                if ($action['title'] == 'Update Item Category') {
                    $update_action = $action;
                }
            }
            $fields = $update_action['fields'];
            foreach($fields as $field) {
                if (isset($_POST[$field['name']])) {
                    $field['value'] = $_POST[$field['name']];
                }
            }
            ?>
            <form role="form" action="/item_categories.php?action=save_item_category" method="post" class="form-horizontal">
            <?php
            foreach($update_action['fields'] as $field) {
                ?>
                <div class="form-group">
                    <label for="<?php print $field['name']; ?>" class="col-sm-2 control-label"><?php print $field['title']; ?></label>
                    <div class="col-sm-3">
                <?php
                if ($field['type'] == 'checkbox') {
                ?>
                    <?php $checked = ($field['value'] == 'true') ? ' checked="checked"' : ''; ?>
                    <input<?php print $checked; ?> type="checkbox" id="<?php print $field['name']; ?>" name="<?php print $field['name']; ?>" value="true" />
                <?php
                }
                if ($field['type'] == 'text' || $field['type'] == 'number' || $field['type'] == 'url' || $field['type'] == 'email') {
                ?>
                    <input type="text" class="form-control" id="<?php print $field['name']; ?>" name="<?php print $field['name']; ?>" maxlength="200" value="<?php echo htmlspecialchars($field['value']); ?>">
                <?php
                }
                if ($field['type'] == 'radio') {
                ?>
                    <select name="<?php print $field['name']; ?>" id="<?php print $field['name']; ?>">
                    <?php
                    foreach($field['options'] as $option_value => $option) {
                        $selected = ($field['value'] == $option_value) ? ' selected="selected"' : '';
                        ?>
                        <option<?php print $selected; ?> value="<?php print $option_value; ?>"><?php print $option; ?></option>
                        <?php
                    }
                    ?>
                    </select>
                    <?php
                }
                ?>
                    </div>
                </div>
                <?php
            }
            ?>
            <input type="hidden" name="resource_uri" value="<?php print htmlspecialchars($_REQUEST['resource_uri'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <button type="submit" class="btn btn-primary">Save Item Category</button>
        </form>
        <?php
        }
    }
}

if ($action == 'view_item_category') {
    ?>
    <h2>View Item Category</h2>
    <?php
    $errors = array();
    $resouce_uri = (isset($_REQUEST['resource_uri']) ? $_REQUEST['resource_uri'] : '');
    if ($resouce_uri == '') {
        $errors[] = 'The required resource_uri is missing. Please click back and try again.';
    }
    if (!count($errors)) {
        $result = $fc->get($resouce_uri);
        $errors = array_merge($errors,$fc->getErrors($result));
        if (!count($errors)) {
            ?>
            <h3><?php print $result['name']; ?></h3>
            <div class="col-md-6">
            <table class="table">
            <?php
            $boolean_fields = array('send_customer_email','send_admin_email');
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
            }
            ?>
            </table>
            <form role="form" action="/item_categories.php?action=edit_item_category_form" method="post" class="form-horizontal">
            <input type="hidden" name="resource_uri" value="<?php print htmlspecialchars($resouce_uri, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="hidden" name="csrf_token" value="<?php print htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" />
            <input type="submit" name="submit" class="btn btn-warning" value="Edit <?php print $result['name']; ?>" />
            </form><br />
            <hr />

            <a class="btn btn-primary" href="/item_categories.php?action=view_item_categories">View All Item Categories</a>
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


if ($action == 'view_item_categories') {
    ?>
    <h2>View item_categories</h2>
    <?php
    $errors = array();
    $item_categories_uri = $_SESSION['item_categories_uri'];
    if (isset($_REQUEST['item_categories_uri'])) {
        $item_categories_uri = $_REQUEST['item_categories_uri'];
    }
    $result = $fc->get($item_categories_uri, array("limit" => 5));
    $errors = array_merge($errors,$fc->getErrors($result));
    if (!count($errors)) {
        ?>
        <h3>item_categories for <?php print $_SESSION['store_name']; ?></h3>
        <?php
        print '<p>Displaying ' . $result['returned_items'] . ' (' . ($result['offset']+1) . ' through ' . min($result['total_items'],($result['limit']+$result['offset'])) . ') of ' . $result['total_items'] . ' total item_categories.</p>'
        ?>
        <nav>
          <ul class="pagination">
            <li>
              <a href="/item_categories.php?action=view_item_categories&amp;item_categories_uri=<?php print urlencode($result['_links']['prev']['href']); ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>
            <li>
              <a href="/item_categories.php?action=view_item_categories&amp;item_categories_uri=<?php print urlencode($result['_links']['next']['href']); ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
              </a>
            </li>
          </ul>
        </nav>
        <table class="table">
        <tr>
            <th>Item Category Name</th>
            <th>Code</th>
            <th>Item Delivery Type</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
        <?php
        foreach($result['_embedded']['fx:item_categories'] as $item_category) {
            ?>
            <tr>
                <td><?php print $item_category['name']; ?></td>
                <td><?php print $item_category['code']; ?></td>
                <td><?php print $item_category['item_delivery_type']; ?></td>
                <td><a class="btn btn-primary" href="/item_categories.php?action=view_item_category&amp;resource_uri=<?php print urlencode($item_category['_links']['self']['href']); ?>">View</a></td>
                <td><a class="btn btn-warning" href="/item_categories.php?action=edit_item_category_form&amp;resource_uri=<?php print urlencode($item_category['_links']['self']['href']); ?>">Edit</a></td>
                <td><a class="btn btn-danger" href="/item_categories.php?action=delete_item_category_form&amp;resource_uri=<?php print urlencode($item_category['_links']['self']['href']); ?>&amp;resource_name=<?php print urlencode($item_category['name']); ?>">Delete</a></td>
            </tr>
            <?php
        }
        ?>
        </table>
        <a class="btn btn-primary" href="/item_categories.php?action=add_item_category_form">Add Item Category</a>
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
    print '<br /><a href="/item_categories.php?action=">Home</a>';
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