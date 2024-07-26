/** @type {import('tailwindcss').Config} */
const colors = require('tailwindcss/colors');
module.exports = {
  content: ['./src/**/*.{html,js,php,css}', './*.{php,html}'],
  theme: {
    extend: {
      colors: {
        textColour: '#dee2e6',
        background: '#212529',
        textAccent: '#b3b4b6',
        accent: '#007bff',
        accentBold: '#0d6efd',
        accentDark: '#0054af',
        secondaryAccent: '#a855f7',
        blackOpaque: '#0000001a',
        menu: '#343a40',
        buttonHover: '#4c5257',
        buttonHoverOpaque: '#4c52571a',
        buttonDisabled: '#4D4F50',
        success: '#68e24d',
        successBold: '#47bf2b',
        warning: '#ff8d30',
        warningBold: '#d2750b',
        error: '#fc2d29',
        errorBold: '#c72522',
      },
      spacing: {
        '400': '400px',
        '304': '304px',
        '280': '280px',
        '240': '240px',
        '120': '120px',
        '80': '80px',
        'graph': '380px',
        'sidebarLarge': '304px',
        'sidebarSmall': '104px',
        'tableWithTabs': '480px',
        '10p': '10%',
        '20p': '20%',
        '25p': '25%',
        '30p': '30%',
        '33p': '33.333333333%',
        '40p': '40%',
        '50p': '50%',
        '60p': '60%',
        '66p': '66.666666666%',
        '70p': '70%',
        '750p': '75%',
        '80p': '80%',
        '90p': '90%',
      },
      scale: {
        '101': '1.01',
        '102': '1.02',
        '103': '1.03',
        '104': '1.04',
      },
      boxShadow: {
        'divider': 'inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15)',
        'chatWindow': '0px 0px 5px 1px rgba(0,0,0,0.6) inset',
        // 'panel': '0 .4em 1em rgba(0, 0, 0, .6)',
      },
      transitionProperty: {
        'outline': 'outline-color, outline-opacity',
      },
      fontFamily: {
        'quicksand': 'Quicksand',
        'assistant': 'Assistant',
      },
      backgroundSize: {
        'size-0': '0% 0%',
        'size-100': '100% 100%',
        'size-200': '200% 200%',
      },
      backgroundPosition: {
        'pos-0': '0% 0%',
        'pos-50': '50% 50%',
        'pos-100': '100% 100%',
        'pos-200': '200% 200%',
      },
      animation: {
        'searchButton': 'moveBackground 0.1s ease-in-out forwards'
      },
      keyframes: {
        'moveBackground': {
          '0%': { 'background-position': '50%' },
          '50%': { 'background-position': '100%' },
          '100%': { 'background-position': '50%' },
        }
      },
    }
  },
  plugins: [
    require('flowbite/plugin')({
      charts: true,
    }),
    require('tailwindcss-3d'),
  ]
};