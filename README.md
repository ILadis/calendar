A ready to deploy CalDAV server based on SabreDAV. 

## Building

The PHP dependency manager Composer is required to build the plugin. For installation instructions follow their [getting started](https://getcomposer.org/) guide.

Then install all required runtime dependencies:

```sh
$ composer install
```

Composer will output an error message if any PHP extensions need to be enabled in order to run SabreDAV. Rerun the above command if necessary.

Then build the deployable PHP archive:

```sh
$ ./bin/build
```

This should create a file named `calendar.phar` in the current directory.

## Running

After building the `calendar.phar` archive, the CalDAV server can be started using PHP's built-in web server:

```sh
$ php -S localhost:8080 ./calendar.phar
```

Then open a web browser and visit:  
http://localhost:8080/calendar

The CalDAV server will create a `db.sqlite` file in its working directory. This SQLite3 database file is used to store all calendar entries.
