Development Documentation
=========================

The MOOC.IP plugin ships with a build script that automates several steps
that are performed during the development cycle:

Compile LESS Files
------------------

Run the build script to compile the LESS files into CSS files and create the
minified productive CSS file (namely ``assets/moocip.min.css``):

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

Create the Plugin Archive
-------------------------

You can create an installable Stud.IP plugin archive using the ``zip`` command:

```bash
$ php build.php zip
```
