# OpenQDA Testing Guide

The commands, described on this page assume, you are located within the `/web` folder.

## Server-Side Tests

We use PEST to run server-side tests. These tests require the views to be rendered and the database to be seedable. Be aware that tests are **destructive** and will **delete all data from the database**.

### Prerequisites

Make sure, to have [OpenQDA installed](../installation/preparations.md) and it's building + running properly.
From there you can move to these next steps:

1. **Install Dependencies**: Ensure all dependencies are installed.

```shell
npm install
```

2. **Render Views with Vite**: Start the development server to render views.

```shell
npm run dev
```

3. **Prepare Testing Environment**: Make sure to have a `.env.testing` file with the correct database settings. This file should include configurations similar to your main `.env` file but with a separate testing database to avoid data loss.

To be sure you have the correct `.env.testing` file, you can copy the `.env.example` file and rename it to `.env.testing`:

```shell
cp .env.example .env.testing
```

### Running Tests

To execute the tests, use the following command:

```shell
./vendor/bin/pest
```

Some tests will be skipped because certain features are not yet implemented. However, all relevant features of the application will be tested.

### Important Notes

- **Destructive Nature of Tests**: These tests will delete all data from the database. Ensure that you are not running tests on a production database.
- **Fixing Issues**: Before requesting a pull request, run the tests and fix any issues that arise.

### Troubleshooting

- **Database Connection Issues**: To avoid using your primary database and risking data corruption, ensure your `.env.testing` file is configured with credentials for a dedicated testing database.
- **Missing Dependencies**: Run `npm install` if you encounter missing module errors.
- **Failed Tests**: Review the test output to identify and fix the issues before re-running the tests.

### Additional Resources

For more information, refer to the [Pest documentation](https://pestphp.com/docs/installation).

## Client-Side Tests

We use vitest to run client-side tests.
These unit- and component-tests do not require the server to run.

To run the client tests you need to make sure, you have the
client-side dependencies installed:

```shell
npm install
```

Still being in the `web` folder and once dependencies are installed, 
you can run unit tests via

```shell
npm run test:unit
```
