# OpenQDA Frontend Testing

The commands, described on this page assume, you are located within the `/web` folder.

## About

We use [vitest](https://vitest.dev/guide/) to run client-side tests.
**These unit- and component-tests do not require the server to run.**


## Run the tests
In order run the client tests you need to make sure, you have the
client-side dependencies installed:

```shell
npm install
```

Still being in the `web` folder and once dependencies are installed, 
you can run unit tests via

```shell
npm run test:unit
```

## About test coverage

We use V8 to generate native coverage for our unit tests.
This helps us to keep track of code-flow that is already covered by tests.
A general rule of thumb is that added features, fixes and tests should
not decrease coverage.

Every improvement to tests is generally welcomed,
especially to files and components that have no or insufficient coverage.
