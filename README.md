# phalcon-expressive

[![Build Status](https://secure.travis-ci.org/xerron/phalcon-expressive.svg?branch=master)](https://secure.travis-ci.org/xerron/phalcon-expressive)
[![Coverage Status](https://coveralls.io/repos/github/xerron/phalcon-expressive/badge.svg?branch=master)](https://coveralls.io/github/xerron/phalcon-expressive?branch=master)

*Simple middleware applications in minutes!*

phalcon-expressive to provide a minimalist middleware framework for PHP, with the following
features:

- Routing. 
- DI Containers
- Optionally, templating. 

## Installation

[skeleton project and installer](https://github.com/xerron/phalcon-expressive-skeleton),

### Using the skeleton + installer

The simplest way to install and get started is using the skeleton project, which
includes installer scripts for choosing a router, dependency injection
container, and optionally a template renderer and/or error handler. The skeleton
also provides configuration for officially supported dependencies.

To use the skeleton, use Composer's `create-project` command:

```bash
$ composer create-project xerron/phalcon-expressive-skeleton <project dir>
```

This will prompt you through choosing your dependencies, and then create and
install the project in the `<project dir>` (omitting the `<project dir>` will
create and install in a `phalcon-expressive-skeleton/` directory).

## Documentation

Documentation is [in the doc tree](doc/book/), and can be compiled using [mkdocs](http://www.mkdocs.org):

```bash
$ mkdocs build
```

Additionally, public-facing, browseable documentation is available at
https://docs.u-w-u.com/phalcon-expressive/