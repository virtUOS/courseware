var gulp   = require('gulp');
var sass   = require('gulp-sass');
var minify = require('gulp-minify-css');
var concat = require('gulp-concat');
var zip    = require('gulp-zip');

gulp.task('default', ['sass']);

gulp.task('sass', function() {
    // place code for your default task here
    return gulp.src(['./blocks/*/css/*.scss', './assets/*.scss'])
        .pipe(sass())
        .pipe(minify())
        .pipe(concat('moocip.min.css'))
        .pipe(gulp.dest('./assets'));
});

/*
gulp.task('js', function() {
    return gulp.src([])
        .pipe(uglify())
        .pipe()
});
*/

gulp.task('zip', ['default'], function() {
    return gulp.src(['./assets/**', './blocks/**', './controllers/**', './migrations/**', './models/**', './vendor/**', './views/**', 'Mooc.php', 'LICENSE', 'README.md'])
        .pipe(zip('moocip.zip'))
        .pipe(gulp.dest('.'))
});
