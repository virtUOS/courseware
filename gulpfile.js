var gulp   = require('gulp');
var sass   = require('gulp-sass');
var minify = require('gulp-minify-css');
var concat = require('gulp-concat');
var zip    = require('gulp-zip');

var paths = {
    styles: ['./blocks/*/css/*.scss', './assets/*.scss']
};


gulp.task('default', ['sass']);

gulp.task('sass', function() {
    // place code for your default task here
    return gulp.src(paths.styles)
        .pipe(sass()).pipe(gulp.dest('blocks'))/*
        .pipe(minify())
        .pipe(concat('moocip.min.css'))
        .pipe(gulp.dest('./assets'))*/;
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
        .pipe(gulp.dest('.'));
});

// Rerun the task when a file changes
gulp.task('watch', function() {
  gulp.watch(paths.styles, ['sass']);
});
