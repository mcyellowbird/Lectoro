/** @type {import('tailwindcss').Config} */
const colors = require('tailwindcss/colors')
module.exports = {
  content: ["./*.html"],
  darkMode: 'class',
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
        'sidebarLarge': '304px',
        'sidebarSmall': '104px',
      },
      scale: {
        '101': '1.01',
        '102': '1.02',
        '103': '1.03',
        '104': '1.04',
      },
      boxShadow: {
        'divider': 'inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15)',
        // 'panel': '0 .4em 1em rgba(0, 0, 0, .6)',
      },
      transitionProperty: {
        'outline': 'outline-color, outline-opacity',
      },
      fontFamily: {
        'quicksand': 'Quicksand',
        'assistant': 'Assistant',
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