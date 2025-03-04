import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "OpenQDA Dev Docs",
  description: "OpenQDA development and deployment documentation.",
  base: '/dev-docs/',
  lang: 'en-US',
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: 'https://avatars.githubusercontent.com/u/153517223?s=320&v=4',
    nav: [
      {text: 'Guide', link: '/introduction/about'},
      {
        text: 'API',  link: '/api.md'
      },
      {
        text: '1.0.0', items: [
          {text: 'Release notes', link: 'https://github.com/openqda/openqda/releases'},
          {text: 'Contributing', link: 'https://github.com/openqda/openqda/blob/main/CONTRIBUTING.md'},
        ]
      }
    ],

    sidebar: [
      {
        text: 'Introduction',
        items: [
          {text: 'About', link: '/introduction/about'},
          {text: 'Tech Stack', link: '/introduction/tech-stack'},
          {text: 'Architecture', link: '/introduction/architecture'},
        ]
      },
      {
        text: 'Installation',
        items: [
          {text: 'Preparations', link: '/installation/preparations'},
          {text: 'Docker', link: '/installation/docker'},
          {text: 'Manual', link: '/installation/manual'}
        ]
      },
      {
        text: 'Development',
        items: [
          { text: 'Backend',         collapsed: false, items: [
              {text: 'Overview', link: '/development/backend/backend'},
              {text: 'Websocket', link: '/development/backend/websockets'},
              {text: 'Testing', link: '/development/backend/testing'},
            ]
          },
          {
            text: 'Frontend',         collapsed: false, items: [
              {text: 'Overview', link: '/development/frontend/frontend'},
              {text: 'Upload Queue', link: '/development/frontend/upload-queue.md'},
              {text: 'Testing', link: '/development/frontend/testing'},
            ]
          }
        ]
      },
      {
        text: 'Plugins',
        items: [
          {text: 'Overview', link: '/plugins/overview'},
        ]
      },
      {
        text: 'Deployment',
        items: [
          {text: 'Overview', link: '/deployment/deployment'},
        ]
      },
      {
        text: 'Legal',
        items: [
          { text: 'Imprint', link: '/imprint' },
          { text: 'Privacy / GDPR', link: '/privacy' },
          { text: 'Code of Conduct', link: '/coc' },
          { text: 'Contact', link: '/contact' },
        ]
      }
    ],

    socialLinks: [
      {icon: 'github', link: 'https://github.com/openqda/openqda'}
    ],

    search: {
      provider: 'local'
    },
    lastUpdated: {
      text: 'Updated at',
      formatOptions: {
        dateStyle: 'full',
        timeStyle: 'medium'
      }
    }
  },
  footer: {
    message: 'OpenQDA and this documentation are released under APGL-3.0 license',
    copyright: 'Copyright © 2023-present The OpenQDA Team'
  }
})
