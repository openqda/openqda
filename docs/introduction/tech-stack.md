# Technology Stack

This page intends to give you a broad overview
of the technologies and tools, involved in the development of OpenQDA.

## OpenQDA server
- Laravel 11
- Php 8.2+
- MySql
- Redis (for websockets and queue elaboration)
- Ubuntu 22.04 (verified to work with OpenQDA)

## RTF Transform Service
- Python 3.*
- Libreoffice
- [Unoconv](https://github.com/unoconv/unoconv)


## Prominent Laravel Packages
- Laravel JetStream 5.0+
  *Authentication + Team Feature*
- Laravel Reverb
  *Websockets*
- Filament
  *Admin Area*
- Laravel Audit
  *Audits for the history of changes*

## Client

For client development you need the current NodeJs LTS release
and NPM, which is distributed with it.

## Client Stack
- Vue 3 + Vite as Frontend
- inertiajs/vue3 for Single Page Application (SPA) and Routing
- Tailwind as our CSS System
- Quill as our Editor for preparation and coding
- D3 for Visualizations

## Code Quality and Development Tools
- prettier for code formatting
- eslint for linting
