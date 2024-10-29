import gulp from "gulp";
import autoprefixer from "gulp-autoprefixer";
import gulpSass from "gulp-sass";
import * as sass from "sass"; // Updated import for Sass

// Initialize gulp-sass with the Sass compiler
const sassCompiler = gulpSass(sass);

// Source sass files
const SASS = "scss";
// Output directory
const CSS = "css";

// Building sass
gulp.task("sass", (done) => {
  gulp
    .src(SASS + "/**/*.scss")
    .pipe(sassCompiler.sync({}).on("error", sassCompiler.logError))
    .pipe(autoprefixer({}))
    .pipe(gulp.dest(CSS));
  done();
});

// Build task: 'gulp build'
gulp.task("build", gulp.series("sass"));

// Watch task: 'gulp watch'
gulp.task("watch", function () {
  return gulp.watch(SASS + "/**/*.scss", gulp.series("sass"));
});

// Default task: 'gulp'
gulp.task("default", gulp.series("build", "watch"));
