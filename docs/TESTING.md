# OpenQDA Testing Guide

## Server-Side Tests

TBD

## Client-Side Tests

We use vitest to run client-side tests.
These unit- and component-tests do not require the server to run.

To run the client tests you need to make sure, you have the
client-side dependencies installed:

```shell
$ cd web
$ npm install
```

Still being in the `web` folder and once dependencies are installed, 
you can run unit tests via

```shell
$ npm run test:unit
```

- Component-tests TBD

## End-To-End (e2e) Tests

We use Cypress to run our e2e tests.
In order to run them, you need to make sure the server
runs as it would during your usual development.

To run the client tests you need to make sure, you have the
client-side dependencies installed:

```shell
$ cd web
$ npm install
```

Still being in the `web` folder and once dependencies are installed,
you can run unit tests via

```shell
$ npm run test:e2e
```

If you want to extend the e2e testsuite, then instead you need to run

```shell
$ npx cypress open
```
