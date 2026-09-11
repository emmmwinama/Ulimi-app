/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["../../app/Views/**/*.php"],
  theme: {
    extend: {
      colors: {
        navy: { DEFAULT: "#0F172A", 700: "#111827", 600: "#475569" },
        blue: { DEFAULT: "#2563EB", 600: "#1D4ED8", 50: "#EFF6FF" },
        sky: { DEFAULT: "#0EA5E9", 600: "#0284C7" },
        teal: { DEFAULT: "#0F766E" },
        danger: { DEFAULT: "#DC2626", 50: "#FFF1F2" },
        amber: { DEFAULT: "#B45309", 50: "#FFFBEB" },
        ice: "#F8FAFC",
      },
      fontFamily: {
        sans: ["Nunito", "Segoe UI", "Arial", "sans-serif"],
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
