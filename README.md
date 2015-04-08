# FoxyClient Playground
A FoxyClient playground of examples using the Foxy Hypermedia API

Starting with the <a href="https://github.com/FoxyCart/foxyclient-php-example">FoxyClient PHP Example</a> code, we'll explore some common uses for the Foxy API. You can follow along with the <a href="https://api.foxycart.com/docs/tutorials">Tutorials section of the Foxy docs</a>.

## First up, Coupons!

Clone the repoe, run `php composer.phar install`, fire up a php server with `php -S localhost:8000`, and visit <a href="http://localhost:8000/coupons.php">http://localhost:8000/coupons.php</a> to get started.

If you've run through the example code already, you should have a client to work with. The first thing you'll want to do is edit bootstrap.php to include your own <code>client_id</code> and <code>client_secret</code>. If you set up your client with a <code>redirect_uri</code> of <code>http://localhost:8000/coupons.php?action=authorization_code_grant</code> you're all set (if not, go ahead and modify your client as needed). When you first try to view your coupons, it will redirect to the OAuth authorization endpoint so you can grant access to your store.
