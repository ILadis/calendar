A ready to deploy CalDAV server based on SabreDAV.

## Building

The [PHP dependency manager Composer](https://getcomposer.org/) is required to build the CalDAV server. The `./build.php` script will automatically download Composer if no `composer.phar` file is found.

The CalDAV server can be built with:

```sh
$ ./build.php build --clean
```

This will download all required runtime dependencies into the `vendor/` directory. Composer will output an error message if any PHP extensions need to be enabled in order to run the CalDAV server. When all required PHP extensions are enabled rerun the `./build.php` script.

If the `./build.php` script finishes successful a file named `calendar.phar` should be created.

## Running

After building `calendar.phar`, the CalDAV server can be started using PHP's built-in web server:

```sh
$ php -S localhost:8080 ./calendar.phar
```

Then open a web browser and visit:  
http://localhost:8080/calendar

The CalDAV server will create a `db.sqlite` file in its working directory. This SQLite3 database file is used to store all calendar entries.
