const config = {
  plugins: {
    "@tailwindcss/postcss": {
      // Minify for `npm run build`, keep readable output while watching.
      optimize: !process.argv.includes("--watch"),
    },
  },
};

export default config;
