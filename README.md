**This is my fork of [orangehill/iseed](https://github.com/orangehill/iseed).** I updated a few things and added iseed:all command.

**Inverse seed generator (Iseed)** is a Laravel package that provides a method to generate a new seed file based on data from the existing database table.

[![Build Status](https://travis-ci.org/SchuBu/iseed.png)](http://travis-ci.org/SchuBu/iseed)
[![Latest Stable Version](https://poser.pugx.org/SchuBu/iseed/v/stable.png)](https://packagist.org/packages/SchuBu/iseed) [![Total Downloads](https://poser.pugx.org/SchuBu/iseed/downloads.png)](https://packagist.org/packages/SchuBu/iseed)
[![Analytics](https://ga-beacon.appspot.com/UA-1936460-35/iseed?useReferrer&flat)](https://github.com/igrigorik/ga-beacon)

## Installation

### 1. Require with [Composer](https://getcomposer.org/)
```console
$ composer require cheesegrits/iseed
```

**Laravel 5.3.7 and below** or **Laravel 4** need specific version.

```console
$ composer require SchuBu/iseed:2.2 # Laravel 5.3.7 and below
$ composer require SchuBu/iseed:1.1 # Laravel 4
```

### 2. Add Service Provider (Laravel 5.4 and below)

Latest Laravel versions have auto dicovery and automatically add service provider - if you're using 5.4.x and below, remember to add it to `providers` array at `/app/config/app.php`:

```php
// ...
SchuBu\Iseed\IseedServiceProvider::class,
```

## Artisan Commands

Iseed comes with two artisan commands:

1. `php artisan iseed`
1. `php artisan iseed:all`

### php artisan iseed

```console
$ php artisan iseed --help
Description:
  Generate seed file from table

Usage:
  iseed [options] [--] <tables>

Arguments:
  tables                                   comma separated string of table names

Options:
      --clean                              clean iseed section
      --force                              force overwrite of all existing seed classes
      --database[=DATABASE]                database connection [default: "mysql"]
      --max[=MAX]                          max number of rows
      --chunksize[=CHUNKSIZE]              size of data chunks for each insert query
      --exclude[=EXCLUDE]                  exclude columns
      --prerun[=PRERUN]                    prerun event name
      --postrun[=POSTRUN]                  postrun event name
      --dumpauto[=DUMPAUTO]                run composer dump-autoload [default: true]
      --noindex                            no indexing in the seed
      --orderby[=ORDERBY]                  orderby desc by column
      --direction[=DIRECTION]              orderby direction
      --classnameprefix[=CLASSNAMEPREFIX]  prefix for class and file name
      --classnamesuffix[=CLASSNAMESUFFIX]  suffix for class and file name
```

### php artisan iseed:all

```console
$ php artisan iseed:all --help
Description:
  Generate seed files for all tables except migrations

Usage:
  iseed:all [options]

Options:
      --force
```

## Artisan Command Options

The primary artisan command (`php artisan iseed`) contains a series of arguments (some required, some optional) you can use when calling the command.

### [table_name]
Mandatory parameter which defines which table/s will be used for seed creation.
Use CSV notation for multiple tables. Seed file will be generated for each table.

Examples:
```console
$ php artisan iseed my_table
```
```console
$ php artisan iseed my_table,another_table
```

### classnameprefix & classnamesuffix
Optionally specify a prefix or suffix for the Seeder class name and file name.
This is useful if you want to create an additional seed for a table that has an existing seed without overwriting the existing one.

Examples:

```console
$ php artisan iseed my_table --classnameprefix=Customized
```
outputs `CustomizedMyTableSeeder.php`

```console
$ php artisan iseed my_table,another_table --classnameprefix=Customized
```
outputs `CustomizedMyTableSeeder.php` and `CustomizedAnotherTableSeeder.php`

```console
$ php artisan iseed my_table --classnamesuffix=Customizations
```
outputs `MyTableCustomizationsSeeder.php`

```console
$ php artisan iseed my_table,another_table --classnamesuffix=Customizations
```
outputs `MyTableCustomizationsSeeder.php` and `AnotherTableCustomizationsSeeder.php`

### force
Optional parameter which is used to automatically overwrite any existing seeds for desired tables.

Example:
The following command will overwrite `UsersTableSeeder.php` if it already exists in Laravel's seeds directory.
```console
$ php artisan iseed users --force
```

### dumpauto
Optional boolean parameter that controls the execution of `composer dump-autoload` command. Defaults to `true`.

Example that will stop `composer dump-autoload` from execution:
```console
$ php artisan iseed users --dumpauto=false
```

### clean
Optional parameter which will clean `app/database/seeders/DatabaseSeeder.php` before creating new seed class.

Example:
```console
$ php artisan iseed users --clean
```

### database
Optional parameter which specifies the DB connection name.

Example:
```console
$ php artisan iseed users --database=mysql2
```

### max
Optional parameter which defines the maximum number of entries seeded from a specified table. In case of multiple tables, limit will be applied to all of them.

Example:
```console
$ php artisan iseed users --max=10
```

### chunksize
Optional parameter which defines the size of data chunks for each insert query.

Example:
```console
$ php artisan iseed users --chunksize=100
```

Please note that some users encountered a problem with large DB table exports. The issue is solved by splitting input data into smaller chunks of elements per insert statement. You may need to change the chunk size value in some extreme cases where a DB table has a large number of records, the chunk size is configurable in Iseed's `config.php` file or via the `artisan` command.

### orderby
Optional parameter which defines the column which will be used to order the results by, when used in conjunction with the max parameter that allows you to set the desired number of exported database entries.

Example:
```console
$ php artisan iseed users --max=10 --orderby=id
```

### direction
Optional parameter which allows you to set the direction of the ordering of results; used in conjuction with orderby parameter.

Example:
```console
$ php artisan iseed users --max=10 --orderby=id --direction=desc
```

### exclude
Optional parameter which accepts comma separated list of columns that you'd like to exclude from tables that are being exported. In case of multiple tables, exclusion will be applied to all of them.

Example:
```console
$ php artisan iseed users --exclude=id
$ php artisan iseed users --exclude=id,created_at,updated_at
```

### prerun
Optional parameter which assigns a laravel event name to be fired before seeding takes place. If an event listener returns `false`, seed will fail automatically.
You can assign multiple preruns for multiple table names by passing an array of comma separated DB names and respectively passing a comma separated array of prerun event names.

Example:
The following command will make a seed file which will fire an event named `someEvent` before seeding takes place.
```console
$ php artisan iseed users --prerun=someEvent
```
The following example will assign `someUserEvent` to `users` table seed, and `someGroupEvent` to `groups` table seed, to be executed before seeding.
```console
$ php artisan iseed users,groups --prerun=someUserEvent,someGroupEvent
```
The following example will only assign a `someGroupEvent` to `groups` table seed, to be executed before seeding. Value for the users table prerun was omitted here, so `users` table seed will have no prerun event assigned.
```console
$ php artisan iseed users,groups --prerun=,someGroupEvent
```

### postrun
Optional parameter which assigns a laravel event name to be fired after seeding takes place. If an event listener returns `false`, seed will be executed, but an exception will be thrown that the postrun failed.
You can assign multiple postruns for multiple table names by passing an array of comma separated DB names and respectively passing a comma separated array of postrun event names.

Example:
The following command will make a seed file which will fire an event named `someEvent` after seeding was completed.
```console
$ php artisan iseed users --postrun=someEvent
```
The following example will assign `someUserEvent` to `users` table seed, and `someGroupEvent` to `groups` table seed, to be executed after seeding.
```console
$ php artisan iseed users,groups --postrun=someUserEvent,someGroupEvent
```
The following example will only assign a `someGroupEvent` to `groups` table seed, to be executed after seeding. Value for the users table postrun was omitted here, so `users` table seed will have no postrun event assigned.
```console
$ php artisan iseed users,groups --postrun=,someGroupEvent
```

### noindex
By using `--noindex` the seed can be generated as a non-indexed array.
The use case for this feature is when you need to merge two seed files.

Example:
```console
$ php artisan iseed users --noindex
```

## Configuration

Iseed comes with the following configuration, to change the default first publish the configuration with:

```console
$ php artisan vendor:publish --provider="SchuBu\Iseed\IseedServiceProvider" --tag="config"
```

### path
Path where the seeders will be generated.

The default is `/database/seeders`.

### seeder_path
Path where the Seeder file is saved.

The default is `/database/seeders/DatabaseSeeder.php`

### seeder_modification
Whether the Seeder should be modified after running the `iseed` command.

The default is `true`.

### chunk_size
Maximum number of rows per insert statement.

The default is `500`.

### stub_path
You may alternatively set a relative path to a custom stub file. Make sure to make path relative to your project root, e.g. `'stub_path' => 'stubs/seeder.stub'` or `'stub_path' => './stubs/seeder.stub'` but **not** `'stub_path' => '/stubs/seeder.stub'`.

The default stub file is located in `/vendor/schubu/iseed/src/SchuBu/Iseed/Stubs/seed.stub`

### insert_command
You may customize the line that preceeds the inserts inside the seeder.

You **MUST** keep both `%s` however, the first will be replaced by the table name and the second by the inserts themselves.

The default is `\DB::table('%s')->insert(%s);`.

## Usage

To generate a seed file for your individual tables simply call:

```php
\Iseed::generateSeed($tableName);
```

`$tableName` needs to define the name of your table. There are also parameters you can pass to the `generateSeed()` method which include:

| Variable       | Description               | Default  | Type     | Required            |
|:---------------|:--------------------------|:---------|:---------|:--------------------|
| $tableName     | Table name                |          | string   | :white_check_mark:  |
| $prefix        | Seeder class prefix       | null     | string   | :x:                 |
| $suffix        | Seeder class suffix       | null     | string   | :x:                 |
| $database      | Database connection name  | null     | string   | :x:                 |
| $max           | Maximum seeded entries    | 0        | integer  | :x:                 |
| $chunkSize     | Size of data chunks       | 0        | integer  | :x:                 |
| $exclude       | Columns to exclude        | null     | string   | :x:                 |
| $prerunEvent   | Prerun event name         | null     | string   | :x:                 |
| $postrunEvent  | Postrun event name        | null     | string   | :x:                 |
| $dumpAuto      | Composer auto-dump        | true     | string   | :x:                 |
| $indexed       | Indexed array             | true     | string   | :x:                 |
| $orderBy       | Column to order by        | null     | string   | :x:                 |
| $direction     | Default sort order        | ASC      | string   | :x:                 |

For example, to generate a seed for your `users` table you would call:

```php
\Iseed::generateSeed('users', 'mysql2', 100);
```

This will create a file inside the `/database/seeders` folder (`/database/seeds` for Laravel 5 to 7 and `/app/database/seeds` for Laravel 4) called `UsersTableSeeder.php` with the contents similar to following example:

```php
<?php
// File: /database/seeders/UsersTableSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder {

    /**
     * Auto generated seeder file.
     *
     * @return void
     */
    public function run()
    {

        \DB::table('users')->delete();

        \DB::table('users')->insert(array (
            0 =>
            array (
                'id' => '1',
                'email' => 'admin@admin.com',
                'password' => '$2y$10$tUGCkQf/0NY3w1l9sobGsudt6UngnoVXx/lUoh9ElcSOD0ERRkK9C',
                'permissions' => NULL,
                'activated' => '1',
                'activation_code' => NULL,
                'activated_at' => NULL,
                'last_login' => NULL,
                'persist_code' => NULL,
                'reset_password_code' => NULL,
                'first_name' => NULL,
                'last_name' => NULL,
                'created_at' => '2013-06-11 07:47:40',
                'updated_at' => '2013-06-11 07:47:40',
            ),
            1 =>
            array (
                'id' => '2',
                'email' => 'user@user.com',
                'password' => '$2y$10$ImNvsMzK/BOgNSYgpjs/3OjMKMHeA9BH/hjl43EiuBuLkZGPMuZ2W',
                'permissions' => NULL,
                'activated' => '1',
                'activation_code' => NULL,
                'activated_at' => NULL,
                'last_login' => '2013-06-11 07:54:57',
                'persist_code' => '$2y$10$C0la8WuyqC6AU2TpUwj0I.E3Mrva8A3tuVFWxXN5u7jswRKzsYYHK',
                'reset_password_code' => NULL,
                'first_name' => NULL,
                'last_name' => NULL,
                'created_at' => '2013-06-11 07:47:40',
                'updated_at' => '2013-06-11 07:54:57',
            ),
        ));
    }

}
```

This command will also update `/database/seeders/DatabaseSeeder.php` (`/database/seeds/DatabaseSeeder.php` for Laravel 5 to 7 and `/app/database/seeds/DatabaseSeeder.php` for Laravel 4) to include a call to this newly generated seed class.

If you wish you can define a custom Iseed template in which all the calls will be placed. You can do this by using `#iseed_start` and `#iseed_end` templates anywhere  within `/database/seeders/DatabaseSeeder.php` (`/database/seeds/DatabaseSeeder.php` for Laravel 5 to 7 and `/app/database/seeds/DatabaseSeeder.php` for Laravel 4), for example:

```php
<?php
// File: /database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
        */
    public function run()
    {
        Eloquent::unguard();

        if(App::environment() == "local")
        {
            throw new \Exception('Only run this from production');
        }

        // All generated seeds are added between #iseed_start & #iseed_end.
        #iseed_start
        $this->call(UsersTableSeeder::class);
        #iseed_end
    }

}
```

Alternatively you can run Iseed from the command line using Artisan, e.g. `php artisan iseed users`. For generation of multiple seed files comma separated list of table names should be send as an argument for command, e.g. `php artisan iseed users,posts,groups`.

In case you try to generate a seed file that already exists the command will ask you a question whether you want to overwrite it or not. If you wish to overwrite it by default use the `--force` artisan command option, e.g. `php artisan iseed users --force`.

If you wish to clear the Iseed template you can use the `--clean` artisan command option, e.g. `php artisan iseed users --clean`. This will clean the template from `database/seeders/DatabaseSeeder.php` before creating the new seed class.

You can specify a db connection that will be used for creation of new seed files by using the `--database=connection_name` artisan command option, e.g. `php artisan iseed users --database=mysql2`.

To limit the number of rows that will be exported from table use the `--max=number_of_rows` artisan command option, e.g. `php artisan iseed users --max=10`. If you use this option while exporting multiple tables the specified limit will be applied to all of them.


