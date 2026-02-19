
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.js',
      refresh: true,
    }),
    vue(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
})


// import { defineConfig } from 'vite'
// import laravel from 'laravel-vite-plugin'
// import vue from '@vitejs/plugin-vue'
// import path from 'path'
// import { VitePWA } from 'vite-plugin-pwa'

// export default defineConfig({
//   plugins: [
//     laravel({
//       input: 'resources/js/app.js',
//       refresh: true,
//     }),
//     vue(),

//     VitePWA({
//       registerType: "autoUpdate",
//       includeAssets: [
//         "favicon.ico",
//         "robots.txt",
//         "apple-touch-icon.png"
//       ],
//       manifest: {
//         name: "CRM App",
//         short_name: "CRM",
//         description: "Aplikasi CRM berbasis Vue dan Laravel",
//         theme_color: "#0d6efd",
//         background_color: "#ffffff",
//         display: "standalone",
//         start_url: "/",
//         orientation: "portrait",
//         icons: [
//           {
//             src: "/pwa-192x192.png",
//             sizes: "192x192",
//             type: "image/png"
//           },
//           {
//             src: "/pwa-512x512.png",
//             sizes: "512x512",
//             type: "image/png"
//           },
//           {
//             src: "/pwa-512x512.png",
//             sizes: "512x512",
//             purpose: "any maskable",
//             type: "image/png"
//           }
//         ]
//       }
//     })
//   ],
//   resolve: {
//     alias: {
//       '@': path.resolve(__dirname, 'resources/js'),
//     }
//   }
// })

