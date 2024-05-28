# How to Contribute to OpenQDA

First of all, thank you for contributing to this project and
helping us to make an impact in the realm of open qualitative data analysis.

> Please read this guide to make it a valuable success!

## Who is this guide for?

This guide targets everyone who wants to contribute to 
- code (features, fixes, dependency updates etc.)
- technical documentation (for user documentation, please see [openqda/user-docs](https://github.com/openqda/user-docs))
- CI/CD
- any other form of automation

Other contributions can be done here:
- [report bugs and issues](https://github.com/openqda/openqda/issues)
- [discuss new features, ideas or questions](https://github.com/orgs/openqda/discussions)

## Why is this guide so important?

There are a few conventions and pattern, for which we will take
a closer look at during our reviews.
These conventions are fundamental to the quality of the code and
the project's overall sustainability but also intend to prevent
common issues that arise during contributions.

Please be aware of the following:



## How to prepare your contribution

If you are not member of the organization then you need to fork the repository.
The GitHub help explains this in much detail: https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/fork-a-repo

Then you need to clone the repository 

### Create a new branch

Make sure, your have [installed OpenQDA](./docs/INSTALLATION.md) and 
that it runs locally.

You should always start your contribution on a new branch, coming from
the latest state of the `main` branch:

```shell
$ get checkout main
$ git pull --ff-only
```

If the pull cannot "fast-forward", then you have remains of changes on your
current branch. Please remove them and make sure your `main` branch is "clean".

> Note: if you have forked the repository and your fork is much behind the main repository
then you need to sync them: https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/syncing-a-fork

Finally, **checkout a new branch** and name it by the following **lowercase** pattern:

```
<type>/<short-summary>
```

where `<type>` is one of the following:

| type      | description                                                 |
|-----------|-------------------------------------------------------------|
| `feature` | Indicates that this branch represents a single new feature  |
| `fix` | Indicates that this branch contains a fix for a specific bug or issue |
|`docs` | This branch is entirely about a specific documentation improvement. Note that feature and fixes may also contain documentation but a `docs` branch only improves documentation |
|`ci` | this branch resolves around CI only |
|`tests`| This branch is only about testing. While other code might be involved or tests might be part of features and fixes, these type of branches mainly focus on testing |

and where `short-summary` is a descriptive and understandable summary of what this
branch is about. It should represent the feature, fix etc. you are about to do.

### Example

Provide a new feature "Awesome Timemachine"

> ✅ Right

```shell
$ git checkout -b feature/awesome-timemachine
```

> ❌ Wrong

```shell
$ git checkout -b feature-awesome-timemachine
```
```shell
$ git checkout -b awesome-timemachine
```
```shell
$ git checkout -b feature-1
```
```shell
$ git checkout -b feature/my-cool-feature
```


## Before committing your work

> Before you commit you should ensure, the code passes
> code quality checks and tests!

### Code quality checks

Make sure your **server code** is well formatted by running

```shell
$ ./vendor/bin/pint --test
```

Make sure the **client code** is well formatted and is passing the lint and formatter by running

```shell
$ npm run lint:check
$ npm run format:check
```

You can use `lint:write` and `format:write` to autofix trivial issues.
Other issues may require manual adjustments.

### Tests

Currently, you can run the following types of tests:

- client-side unit tests
- full application end-to-end (e2e) tests

You can run them via

```shell
$ npm run test:unit
$ npm run test:e2e
```

> Make sure your contribution does not break any existing tests!


### Conventions for programming

- If you work on the backend, you should follow [Laravel best practices](https://github.com/alexeymezenin/laravel-best-practices)
- If you work on the frontend, you should follow
  - [Vue best practices](https://learnvue.co/articles/vue-best-practices)
  - [Idiomatic js](https://github.com/rwaldron/idiomatic.js)
- If a contribution involves changes to `.env` then this change must be reflected in `.env.example`
- Do not hard-code URLs, keys, secrets or other environment-specific settings and names in the code but use `.env`
  - read the [dotenv guide](https://github.com/motdotla/dotenv) on what it is and how to works
- Never `git add` any file that contains secrets, such as `.env`! **NEVER**
- If you add a new feature, then you should add tests
- If you change existing code, then make sure tests don't break or add missing tests or update tests accordingly

## Committing your contribution

Once your contribution is ready to be committed you should make
the commit as understandable as possible.

We aim to use [conventional commits](https://www.conventionalcommits.org) where possible.
