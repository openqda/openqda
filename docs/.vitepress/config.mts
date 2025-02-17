import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "OpenQDA Dev Docs",
  description: "OpenQDA development and deployment documentation.",
  base: '/openqda/',
  lang: 'en-US',
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: 'https://avatars.githubusercontent.com/u/153517223?s=320&v=4',
    nav: [
      {text: 'Guide', link: '/introduction/about'},
      {
        text: 'API', items: [
          {text: 'Client', link: '/api/client/'}
        ]
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
          {text: 'Backend', link: '/development/backend'},
          {text: 'Frontend', link: '/development/frontend'},
          {text: 'Websocket', link: '/development/websockets'},
          {text: 'Testing', link: '/development/testing'},
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
  }
})
