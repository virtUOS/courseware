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

Create the Plugin Archive
-------------------------

You can create an installable Stud.IP plugin archive using the ``zip`` command:

```bash
$ php build.php zip
```
