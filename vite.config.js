import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import path from "path";

export default defineConfig({
    resolve: {
        alias: {
            // Package ships no dist when installed from git; build from source
            "@adminintelligence/js-log-shipper": path.resolve(
                __dirname,
                "node_modules/@adminintelligence/js-log-shipper/src/index.ts"
            ),
        },
    },
    plugins: [
        laravel({
            input: [
                "resources/js/app.js",
                "resources/css/app.css"
            ],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
});
