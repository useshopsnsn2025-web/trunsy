import { defineConfig } from "vite";
import uni from "@dcloudio/vite-plugin-uni";
import fs from "fs";
import path from "path";

// OAuth 回调页面中间件
function oauthCallbackPlugin() {
  return {
    name: 'oauth-callback',
    configureServer(server: any) {
      server.middlewares.use((req: any, res: any, next: any) => {
        if (req.url === '/oauth-callback') {
          const filePath = path.resolve(__dirname, 'public/oauth-callback.html');
          if (fs.existsSync(filePath)) {
            res.setHeader('Content-Type', 'text/html');
            res.end(fs.readFileSync(filePath, 'utf-8'));
            return;
          }
        }
        next();
      });
    }
  };
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [uni(), oauthCallbackPlugin()],
  define: {
    // vue-i18n feature flags for proper tree-shaking
    __VUE_I18N_FULL_INSTALL__: true,
    __VUE_I18N_LEGACY_API__: false,
    __INTLIFY_PROD_DEVTOOLS__: false,
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern', // 使用新版 Sass API，消除弃用警告
        silenceDeprecations: ['legacy-js-api'], // 静默旧版 JS API 警告
      },
    },
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:80',
        changeOrigin: true,
      }
    }
  }
});
