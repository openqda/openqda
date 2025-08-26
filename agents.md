# Agent Instructions

## Repository Layout
- `.github` - GitHub configuration files (workflows, issue templates, etc.) - do not change anything in here
- `docs` - Developer documentation (this is a VitePress application)
- `services` - External plugins, runnable as standalone services
- `web` - The main web application (a Laravel application with Vue.js frontend)
## General Guidelines

### Laravel Backend
- The current Laravel version is 11
- Do not change public API signatures
- Prefer Laravel Eloquent for database interactions
- Generally prefer [Laravel best practices](https://github.com/alexeymezenin/laravel-best-practices) and
  techniques from [Laravel From Scratch](https://laracasts.com/series/laravel-8-from-scratch) and the Laravel documentation.

### Vue.js Frontend
- The current Vue.js version is 3
- The current Tailwind version is 3.4
- Use the Composition API and composables
- Do not directly manipulate the DOM; use Vue's reactivity system
- Do not directly use fetch or axios; use the provided API services that use the `BackendRequest` class
- Prefer [Vue.js best practices](https://vuejs.org/guide/best-practices/) and techniques from the Vue.js documentation.
- Prefer [Tailwind CSS best practices](https://tailwindcss.com/docs/best-practices)

### Documentation
- The documentation is built using VitePress
- Documentation source files are located in the `docs` folder
- Documentation is written in Markdown and US English

## Setup Environment
- Prepare your environment by following the steps in `docs/installation/preparations.md`
- Follow the docker installation guide in `docs/installation/docker.md`

## Running Tests
- Backend tests: `./vendor/bin/sail artisan test`
- Backend lint: `./vendor/bin/sail pint`
- Frontend tests: `./vendor/bin/sail npm run test:unit`
- Frontend lint: `./vendor/bin/sail npm run lint:write`
- Frontend formatting via prettier: `./vendor/bin/sail npm run format:write`

## Building
- Frontend build: `./vendor/bin/sail npm run build`
- Docs: `./vendor/bin/sail npm run build:docs`

## Commits
- Follow the commit guidelines in `CONTRIBUTING.md`
- Include issue numbers in commit messages when applicable
- At the end of the commit message, add your agent name in brackets
- Example: `fix: user authentication issue (#123) [AgentName]`
