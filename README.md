<p align="center">
  <img src="./logo.png" width="150px">
</p>

# bedouin
A file-based PHP routing system.

Two files:
- `cartograph.php` script that generates the map file
- `bedouin.php` script that routes according to the map file

## installation
```bash
wget https://raw.githubusercontent.com/mark-veres/bedouin/main/bedouin.php
```

```bash
wget https://raw.githubusercontent.com/mark-veres/bedouin/main/cartograph.php
```

## example setup

```php
# index.php
require_once "bedouin.php";

$router = new \Bedouin\Router;
$router->loadMap("map.json");
$route = $router->currentRoute();
if (isset($route->file)) include $route->file;
```

```php
# gen_map.php
require_once "./cartograph.php";
$cart = new \Bedouin\Cartograph;
$cart->printMap("map.json");
```

## usage
- copy the `bedouin.php` and `cartograph.php` files in the desired directory
- create the `routes` and `static` directory
- create an `index.php` file will handle all requests
- redirect all requests to `index.php`
- create another PHP file that will use the cartograph API to generate the map

## directory structure
|file|url|method|
|---|---|---|
|`/routes/index.php`|`/`|all|
|`/routes/index.get.php`|`/`|get|
|`/routes/about.php`|`/about`|all|
|`/posts/index.php`|`/posts`|all|
|`/posts/[slug]/index.php`|`/posts/test`<br/>`/posts/bla-bla`|all|
|`/posts/[slug]/new.post.php`|`/posts/test/new`|post|

## 404 pages
- create a `404.php` file in the `routes` folder
- this file does not support custom HTTP methods

## static files
- put all your static files in the `static` directory
- access these files at the `/static/*` url
- file names are case- and extension-sensitive

> [!NOTE]
> coming soon:
> - middleware
> - accessing dynamic parameters from scripts
> - map splits (performance optimization when dealing with many routes)
> - templates

> [!TIP]
> Redirecting all requests to `bedouin.php` on an Apache server.
> ```apacheconf
> RewriteEngine On
> RewriteCond %{REQUEST_FILENAME} !-f
> RewriteRule ^(.*)＄ index.php
> ```

> [!TIP]
> Redirecting all requests to `bedouin.php` with the PHP built-in server
> ```bash
> php -S localhost:8080 bedouin.php
> ```