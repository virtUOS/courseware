module.exports = {
    publicPath: '../../../plugins_packages/virtUOS/Courseware/views/block_manager',
    outputDir: '../../views/block_manager',
    assetsDir: '../../assets/vue',
    filenameHashing: false,
    pages: {
        index: {
            entry: 'src/index.js',
            template: 'public/index.php',
            filename: 'index.php'
        }
    },

    lintOnSave: undefined
};
