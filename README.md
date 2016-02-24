# FoxyClient Playground
A FoxyClient playground of examples using the Foxy Hypermedia API

Starting with the <a href="https://github.com/FoxyCart/foxyclient-php-example">FoxyClient PHP Example</a> code, we'll explore some common uses for the Foxy API. You can follow along with the <a href="https://api.foxycart.com/docs/tutorials">Tutorials section of the Foxy docs</a>.

 1. Clone the repo with `git clone https://github.com/FoxyCart/foxyclient-php-playground`
 2. run `php composer.phar install`
 3. Modify bootstrap.php  
     If you've run through the example code already, you should have an OAuth client to work with. Edit bootstrap.php to include your own <code>client_id</code>, <code>client_secret</code>, and <code>refresh_token</code> with the <code>store_full_access</code> scope. If you don't have the refresh token available, set up your client with a <code>redirect_uri</code> of <code>http://localhost:8000/coupons.php?action=authorization_code_grant</code> (You can modify your client using your access token with the <code>client_full_access</code> scope you should have when creating the client). When you first try to view your coupons, it will redirect to the OAuth authorization endpoint so you can grant access to your store.
 4. Start up a local php server with `php -S localhost:8000`
 5. Visit <a href="http://localhost:8000/">http://localhost:8000/</a> to get started. 

We currently have examples in the playground for managing coupons, coupon codes, and item categories.   
