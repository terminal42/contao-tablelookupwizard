const Encore = require('@terminal42/contao-build-tools');

module.exports = Encore()
    .setOutputPath('public/')
    .setPublicPath('/bundles/terminal42tablelookupwizard')
    .addEntry('tablelookupwizard', './assets/tablelookupwizard.js')
    .configureCssLoader((cssLoaderOptions) => {
        cssLoaderOptions.url = {
            filter: url => !url.startsWith('/')
        };
    })
    .getWebpackConfig()
;
