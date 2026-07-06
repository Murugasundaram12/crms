const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css');

mix.options({
    processCssUrls: false,
    postCss: [require('autoprefixer')],
});

mix.webpackConfig({
    output: {
        publicPath: '/',
        chunkFilename: 'js/chunks/[name].[chunkhash].js',
    },
});

mix.extract(['vue', 'jquery', 'lodash', 'bootstrap']);

mix.setPublicPath('public');
mix.copy('node_modules/boxicons/fonts', 'public/assets/vendor/fonts/boxicons');

if (mix.inProduction()) {
    mix.version();
}