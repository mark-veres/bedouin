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

## usage
- create the `routes` directory in same one as `bedouin.php`
- run the `cartograph.php` script when you want to generate your map
- route all requests to the `bedouin.php` file

## directory structure
|file|url|method|
|---|---|---|
|`/routes/index.php`|`/`|all|
|`/routes/index.get.php`|`/`|get|
|`/routes/about.php`|`/about`|all|
|`/posts/index.php`|`/posts`|all|
|`/posts/[slug]/index.php`|`/posts/test`  `/posts/bla-bla`|all|
|`/posts/[slug]/new.post.php`|`/posts/test/new`|post|

> [!NOTE]
> coming soon:
> - middleware
> - accessing dynamic parameters from scripts