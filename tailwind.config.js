/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.{blade.php,js,jsx,ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'bg': '#0B0B10',
        'surface': '#131319',
        'surface-2': '#1A1A22',
        'border': 'rgba(255, 255, 255, 0.06)',
        'text': '#F2F2F7',
        'text-muted': '#8B8B9A',
        'accent': '#8B5CF6',
        'accent-light': '#A78BFA',
        'positive': '#A3E635',
        'negative': '#F0502A',
      },
      fontFamily: {
        'sans': '"Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, sans-serif',
        'mono': '"IBM Plex Mono", monospace',
      },
      borderRadius: {
        'card': '20px',
        'btn': '12px',
      },
      spacing: {
        'gutter': '40px',
      },
    },
  },
  plugins: [],
}
