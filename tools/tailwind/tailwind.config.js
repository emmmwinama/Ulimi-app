/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["../../app/Views/**/*.php"],
  theme: {
    extend: {
      colors: {
        navy: { DEFAULT: "#0B1220", 700: "#18233A", 600: "#2A3A57" },
        blue: { DEFAULT: "#0284C7", 600: "#0369A1", 50: "#E0F2FE" },
        cyan: { DEFAULT: "#06B6D4" },
        teal: { DEFAULT: "#0D9488" },
        danger: { DEFAULT: "#DC2626", 50: "#FEF2F2" },
        amber: { DEFAULT: "#B45309", 50: "#FFFBEB" },
        ice: "#F7F9FC",
      },
      fontFamily: {
        sans: ["Inter", "Segoe UI", "Roboto", "-apple-system", "BlinkMacSystemFont", "Helvetica Neue", "Arial", "sans-serif"],
        mono: ["ui-monospace", "SF Mono", "Cascadia Mono", "Menlo", "Consolas", "monospace"],
      },
      boxShadow: {
        xs: "0 1px 2px rgba(15,23,42,.05)",
        sm: "0 1px 2px rgba(15,23,42,.05), 0 1px 3px rgba(15,23,42,.08)",
        md: "0 2px 6px rgba(15,23,42,.06), 0 8px 20px -6px rgba(15,23,42,.12)",
        lg: "0 12px 32px -8px rgba(15,23,42,.22)",
        focus: "0 0 0 3px rgba(2,132,199,.18)",
      },
      borderRadius: { xl2: "16px" },
    },
  },
  plugins: [],
};
