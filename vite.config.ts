import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd());
    const hmrHost = env.VITE_HMR_HOST ?? 'localhost';

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.ts'],
                ssr: 'resources/js/ssr.ts',
                refresh: true,
            }),
            tailwindcss(),
            wayfinder({
                formVariants: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],

        // ✅ Docker + NPM(反代網域) 開發設定
        server: {
            host: '0.0.0.0', // 讓容器外可連到 Vite
            port: 5173,
            strictPort: true,

            hmr: {
                host: hmrHost,
                protocol: 'ws',
                // ✅ 讓 WS 有明確路徑，避免撞到 Laravel
                path: '/vite',
                clientPort: 80,
            },

            // Docker volume 檔案監聽常需要 polling 才穩
            watch: {
                usePolling: true,
                interval: 250,
                ignored: [
                    '**/volumes/**',
                    '**/storage/**', // 建議一併忽略（log/session/cache 也會狂寫）
                    '**/vendor/**', // PHP vendor 也不需要 watch
                    '**/node_modules/**',
                ],
            },
        },
    };
});
