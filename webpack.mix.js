const mix = require('laravel-mix');

mix
    .setPublicPath('dist');

mix
    .js('resources/scripts/admin-lf.js', 'js')
    .js('resources/scripts/frontend.js', 'js')
    .sass('resources/styles/admin-lf.scss', 'css')
    .sass('resources/styles/lexoforms-frontend.scss', 'css')
    .options({
        processCssUrls: false,
    });

mix
    .version()
    .sourceMaps();
