# OpenQDA Backend Tests

The commands, described on this page assume, you are located within the `/web` folder.

## Server-Side Tests

We use PEST to run server-side tests. These tests require the views to be rendered and the database to be seedable. Be aware that tests are **destructive** and will **delete all data from the database**.

### Prerequisites

Make sure, to have [OpenQDA installed](../../installation/preparations.md) and it's building + running properly.
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

To execute the tests, use the following command within the `web` folder:

```shell
./vendor/bin/pest
```

If you are using Docker then you need to open a separate terminal (window while Docker compose is up) and run:

```shell
./vendor/bin/sail artisan test --parallel --env=testing
```

Some tests will be skipped because certain features are not yet implemented. However, all relevant features of the application will be tested.

### Running the Linter / Formatter

To ensure code quality and consistency, you can run the linter and formatter, named `pint` using the following command:

```shell
./vendor/bin/pint
```

If you are using Docker then you to run in separate terminal (window while Docker compose is up) the following command:

```shell
./vendor/bin/sail pint
```

It will automatically fix any formatting issues in your codebase.


### Important Notes

- **Destructive Nature of Tests**: These tests will delete all data from the database. Ensure that you are not running tests on a production database.
- **Fixing Issues**: Before opening a pull request, run the tests and linter and fix any issues that arise.

### Troubleshooting

- **Database Connection Issues**: To avoid using your primary database and risking data corruption, ensure your `.env.testing` file is configured with credentials for a dedicated testing database.
- **Missing Dependencies**: Run `npm install` if you encounter missing module errors.
- **Failed Tests**: Review the test output to identify and fix the issues before re-running the tests.

### Additional Resources

For more information, refer to the [Pest documentation](https://pestphp.com/docs/installation).
