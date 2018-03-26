Development Documentation
=========================

Build Courseware <= 2.1.7
-----------------------
The MOOC.IP plugin ships with a build script that automates several steps which
are performed during the development cycle. When you cloned the plugin source
code using Git, you'll have to install all dependencies. After that, you have
to compile LESS files into CSS.

### Installing Dependencies

The MOOC.IP plugin uses [Composer](https://getcomposer.org/) as the dependency
manager. Make sure that you have [installed it globally](https://getcomposer.org/doc/00-intro.md).
Then, run the ``composer`` command to install all required dependencies:

```bash
$ composer install
```

### Compile LESS Files

Run the build script to compile the LESS files into CSS files and create the
minified productive CSS file (namely ``assets/courseware.min.css``):

```bash
$ php build.php less
```

If you frequently modify the LESS files and don't want to compile them after
each modification manually, you can use the ``watch`` task:

```bash
$ php build.php watch
```

The ``watch`` task periodically checks for changes in the plugin's asset files
and dumps them on demand. You can adjust the timeout between to cycles by passing
the time to sleep in second as an argument to the task (the default timeout is
five seconds):

```bash
$ php build.php watch 10
```

### Create the Plugin Archive

You can create an installable Stud.IP plugin archive using the ``zip`` command
(make your that you have [installed all dependencies](#installing-dependencies)
and that you have [compiled the LESS files](#compile-less-files)):

```bash
$ php build.php zip
```

Build Courseware >= 3.0.0
-----------------------

Get composer and install with the composer.phar file:
```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install
php composer.phar update
```

Use npm to download and create necessary files:
```bash
npm install
npm run build:dev
```
From this point you can develop and make changes. If you change some js or less files just call npm run build:dev to build new static js and css files.

If you like to have a ready to install zip file:
```bash
npm run zip
```

**Note:**  
 [Nodejs](https://nodejs.org/en/download/package-manager/) and npm must be installed on your system!
 
 
 
How to build a block for Courseware 3.0 and 4.0
------------------------------------------------

Each courseware block is in block folder. It contains the block object and the folders css, js, schema and templates.
Look at the [DemoBlock](demoblock.zip) to see how it works.
After building your new block, it has to be imoprted into the assets/js/courseware.js before creating the new dependencies.
Just insert  ```import 'DemoBlock/js/DemoBlock'```  after all other imports and use npm run build:dev.
