import { defineConfig } from 'cypress';

export default defineConfig({
    e2e: {
        baseUrl: 'http://127.0.0.1:8000',
        specPattern: 'cypress/e2e/**/*.cy.js',
        supportFile: false,
        video: false,
        screenshotOnRunFailure: false,
        defaultCommandTimeout: 10000,
        pageLoadTimeout: 60000,
        viewportWidth: 1280,
        viewportHeight: 800,
    },
});
