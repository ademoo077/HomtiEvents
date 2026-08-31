/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/Views/**/*.php',
    './public/assets/js/**/*.js',
    './resources/css/**/*.css',
  ],
  // Bootstrap reste la base de reset/layout pendant la migration progressive.
  // Preflight est désactivé pour ne PAS écraser le reset Bootstrap ni les
  // styles existants — on évite ainsi toute régression visuelle.
  corePlugins: {
    preflight: false,
  },
  theme: {
    // Design tokens unifiés -> miroir des variables CSS --wh-* (design-tokens.css)
    extend: {
      colors: {
        wh: {
          blue: 'var(--wh-blue)',
          'blue-dark': 'var(--wh-blue-dark)',
          'blue-soft': 'var(--wh-blue-soft)',
          green: 'var(--wh-green)',
          'green-soft': 'var(--wh-green-soft)',
          red: 'var(--wh-red)',
          'red-soft': 'var(--wh-red-soft)',
          amber: 'var(--wh-amber)',
          'amber-soft': 'var(--wh-amber-soft)',
          cyan: 'var(--wh-cyan)',
          'cyan-soft': 'var(--wh-cyan-soft)',
          purple: 'var(--wh-purple)',
          'purple-soft': 'var(--wh-purple-soft)',
          gray: 'var(--wh-gray)',
          'gray-soft': 'var(--wh-gray-soft)',
          'gray-light': 'var(--wh-gray-light)',
          border: 'var(--wh-border)',
          'border-strong': 'var(--wh-border-strong)',
          text: 'var(--wh-text)',
          'text-muted': 'var(--wh-text-muted)',
        },
        surface: {
          DEFAULT: 'var(--wh-card-bg)',
          soft: 'var(--wh-bg-soft)',
        },
        sidebar: {
          bg: 'var(--wh-sidebar-bg)',
          text: 'var(--wh-sidebar-text)',
          active: 'var(--wh-sidebar-active)',
        },
      },
      fontFamily: {
        sans: ['var(--wh-font-sans)'],
        heading: ['var(--wh-font-heading)'],
        mono: ['var(--wh-font-mono)'],
      },
      borderRadius: {
        wh: 'var(--wh-radius)',
        'wh-lg': 'var(--wh-radius-lg)',
        'wh-xl': 'var(--wh-radius-xl)',
      },
      boxShadow: {
        wh: 'var(--wh-shadow)',
        'wh-lg': 'var(--wh-shadow-lg)',
        'wh-xl': 'var(--wh-shadow-xl)',
        'wh-float': 'var(--wh-shadow-float)',
      },
      transitionTimingFunction: {
        wh: 'var(--wh-ease)',
        'wh-out': 'var(--wh-ease-out)',
      },
      // Sidebar collapsed env (inline utility pour outils de tooltip au hover)
      width: {
        sidebar: 'var(--wh-sidebar-w)',
        'sidebar-collapsed': 'var(--wh-sidebar-w-collapsed)',
      },
      zIndex: {
        header: 'var(--wh-z-header)',
        sidebar: 'var(--wh-z-sidebar)',
        palette: 'var(--wh-z-palette)',
      },
    },
  },
  plugins: [],
};
