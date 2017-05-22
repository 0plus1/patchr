![overview-wide](https://cloud.githubusercontent.com/assets/420815/23929498/c9fccaca-097a-11e7-82c3-07df37ddc32a.jpg)


# Patchr - MySQL version control

Patchr allows you to version control your database changes, allowing teams to easily share and review database changes.

Database version control is a very important process of modern web development, providing an accurate history of schema and data changes.
Most modern frameworks offers database agnostic version control under the term *"migrations"*.

Patchr uses a different approach, allowing you to commit **raw** SQL, more compatible with legacy system and easier to understand by new devs coming onboard.

Patchr is developed with deployment in mind, exposing a comprehensive command line API and it is currently used in some large scale corporate applications.

## Documentation

[Patchr on Gitbook](https://0plus1.gitbooks.io/patchr/)

## Tests

```./vendor/bin/phpunit```
System wide installs of phpunit might not work due to potentially different versions, please rely on above command.

## Frameworks
Patchr is framework agnostic, it can easily be added to any existing framework/project. These are the official wrappers for commonly used frameworks:

* Laravel ([0plus1/patchr-laravel](https://github.com/0plus1/patchr-laravel))

## Roadmap

* Decouple Model class from Mysqli to create adapters for other RDBMS
* Expand unit tests coverage