// eslint-disable-next-line @typescript-eslint/no-var-requires
const DumpBuildTimestampPlugin = require('./scripts/plugins/DumpBuildTimestampPlugin');

module.exports = {
  css: {
    loaderOptions: {
      sass: {
        additionalData: `@import "@/core/styles";`,
      },
    },
    extract: true,
  },
  configureWebpack: {
    resolve: {
      alias: {
        '@cia-ferias/core': '@/core',
        '@cia-ferias/components': '@/core/components',
      },
    },
    plugins: [new DumpBuildTimestampPlugin()],
  },
  chainWebpack: (config) => {
    config.plugins.delete('html');
    config.plugins.delete('preload');
    config.plugins.delete('prefetch');
  },
  publicPath: '.',
  filenameHashing: false,
  runtimeCompiler: true,
};
