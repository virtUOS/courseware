var gulp   = require('gulp'),
    less   = require('gulp-less'),
    minify = require('gulp-minify-css'),
    concat = require('gulp-concat'),
    zip    = require('gulp-zip');

var paths = {
    styles: ['./blocks/*/css/*.less', './assets/*.less', "!./blocks/*/css/_*.less"]
};


gulp.task('default', ['zip']);

gulp.task('zip', function() {
    return gulp.src([
        'assets/**',
        'blocks/**',
        'controllers/**',
        'migrations/**',
        'models/**',
        'vendor/**',
        'views/**',
        'LICENSE',
        'Mooc.php',
        'plugin.manifest',
        'README.md'
    ], { mark: true, cwdbase: true })
    .pipe(zip('moocip.zip'))
    .pipe(gulp.dest('.'));
});

// Rerun the task when a file changes
gulp.task('watch', function() {
  gulp.watch(paths.styles, ['less']);
});
