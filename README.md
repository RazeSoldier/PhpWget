# PhpWget - Written in php download tool

[![Build Status](https://travis-ci.org/RazeSoldier/PhpWget.svg?branch=master)](https://travis-ci.org/RazeSoldier/PhpWget)

== Main Feature ==
* Download files from Internet
* Extract tar/zip archive after download

== Usage ==

PhpWget is a command-line script, can't be used on web.

```
php <script name> -u=<file URL> [options]
php <script name> -h
```

You can also use phar archive of PhpWget. In production, this way is recommended.
Just run build.php, build script will build a phar archive to the current directory.
The phar archive can be used like normal php file.

== Compatibility ==

PhpWget is compatible with PHP 5.5.24 to PHP 7.2.

In PHP 5.5.24- version, PhpWget can't extract BSD generated tar file, other features is OK. [Bug #1](https://github.com/RazeSoldier/PhpWget/issues/1)