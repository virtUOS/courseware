var gulp   = require('gulp'),
    sass   = require('gulp-sass'),
    minify = require('gulp-minify-css'),
    concat = require('gulp-concat'),
    zip    = require('gulp-zip');

var paths = {
    styles: ['./blocks/*/css/*.scss', './assets/*.scss']
};


gulp.task('default', ['sass']);

gulp.task('sass', function() {
    // place code for your default task here
    return gulp.src(paths.styles, {base: './'})
        .pipe(sass()).pipe(gulp.dest('.'))
        .pipe(minify())
        .pipe(concat('moocip.min.css'))
        .pipe(gulp.dest('./assets'));
});

gulp.task('zip', ['default'], function() {
    return gulp.src(['./assets/**', './blocks/**', './controllers/**', './migrations/**', './models/**', './vendor/**', './views/**', 'Mooc.php', 'LICENSE', 'README.md'])
        .pipe(zip('moocip.zip'))
        .pipe(gulp.dest('.'));
});

// Rerun the task when a file changes
gulp.task('watch', function() {
  gulp.watch(paths.styles, ['sass']);
});
