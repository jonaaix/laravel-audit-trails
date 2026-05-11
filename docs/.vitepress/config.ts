import { defineConfig } from 'vitepress';

export default defineConfig({
  title: 'Laravel Audit Trails',
  description: 'Lightweight audit-trail package for Laravel',
  base: '/laravel-audit-trails/',
  cleanUrls: true,
  lastUpdated: true,

  head: [
    ['link', { rel: 'icon', type: 'image/webp', href: '/laravel-audit-trails/logo.webp' }],
    ['meta', { name: 'theme-color', content: '#3a72b8' }],
  ],

  themeConfig: {
    logo: '/logo.webp',
    siteTitle: 'Laravel Audit Trails',

    nav: [
      { text: 'Guide', link: '/installation' },
      { text: 'GitHub', link: 'https://github.com/jonaaix/laravel-audit-trails' },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/' },
          { text: 'Installation', link: '/installation' },
          { text: 'Usage', link: '/usage' },
          { text: 'Configuration', link: '/configuration' },
          { text: 'AI / Laravel Boost', link: '/ai' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/jonaaix/laravel-audit-trails' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © jonaaix',
    },

    search: {
      provider: 'local',
    },
  },
});
