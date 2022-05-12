# Configuring CORS
**lumen-app-email** uses [fruitcake/laravel-cors](https://github.com/fruitcake/laravel-cors) for enabling
[Cross-Origin Resource Sharing](http://enable-cors.org/).
<br>
A configuration template can be found in ```config/cors.php.example```. You need to create a file named
```config/cors.php``` - basically the configuration of ```config/cors.php.example``` should work, but if
you need to set specific options, this would be the place to do so.

#### Options

| Option                   | Description                                                              | Default value |
|--------------------------|--------------------------------------------------------------------------|---------------|
| paths                    | You can enable CORS for 1 or multiple paths, eg. `['api/*'] `            | `[]`          |
| allowed_origins          | Matches the request origin. Wildcards can be used, eg. `*.mydomain.com` or `mydomain.com:*`  | `['*']`       |
| allowed_origins_patterns | Matches the request origin with `preg_match`.                            | `[]`          |
| allowed_methods          | Matches the request method.                                              | `['*']`       |
| allowed_headers          | Sets the Access-Control-Allow-Headers response header.                   | `['*']`       |
| exposed_headers          | Sets the Access-Control-Expose-Headers response header.                  | `false`       |
| max_age                  | Sets the Access-Control-Max-Age response header.                         | `0`           |
| supports_credentials     | Sets the Access-Control-Allow-Credentials header.                        | `false`       |
