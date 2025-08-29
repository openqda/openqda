
# How to Contribute to OpenQDA

First of all, thank you for contributing to this project and
helping us to make an impact in the realm of open qualitative data analysis.

> Please read this guide to make it a valuable success!

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
## Table of Contents

- [Who is this guide for?](#who-is-this-guide-for)
- [Why is this guide important?](#why-is-this-guide-important)
- [How to prepare your contribution](#how-to-prepare-your-contribution)
  - [Create a new branch](#create-a-new-branch)
  - [Examples](#examples)
- [Before committing your work](#before-committing-your-work)
  - [Code quality checks](#code-quality-checks)
  - [Conventions for programming](#conventions-for-programming)
  - [Tests](#tests)
- [Committing your contribution](#committing-your-contribution)
- [Pull request and review process](#pull-request-and-review-process)

*generated with [DocToc](https://github.com/thlorenz/doctoc)*
<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Who is this guide for?

This guide targets everyone who wants to contribute to 
- code (features, fixes, plugins, dependency updates etc.)
- technical documentation (APIs, guides, tutorials, etc.) 
- CI/CD and other forms of automation
- translation (i18n), language, grammar and typos

Other contributions can be done here:
- [user documentation](https://github.com/openqda/user-docs)
- [report bugs and issues](https://github.com/openqda/openqda/issues)
- [discuss new features, ideas or questions](https://github.com/orgs/openqda/discussions)

## Why is this guide important?

There are a few conventions and pattern, for which we will take
a closer look at during our reviews.
These conventions are fundamental to the quality of the code and
the project's overall sustainability but also intend to prevent
common issues that arise during contributions.

> If in doubt: ask for help! We aim to support you in the
> process, especially if you have less to no experience in
> contributing to open source projects.

## How to prepare your contribution

If you are not member of the organization then you need to fork the repository.
The GitHub help explains this in much detail: https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/fork-a-repo

Then you need to clone the repository 

### Create a new branch

Make sure, that you have [installed OpenQDA](https://openqda.github.io/openqda/installation/preparations.html) and 
that it runs locally.

You should always start your contribution on a new branch, coming from
the latest state of the `main` branch:

```shell
$ git checkout main
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

> There should never be `openqda` being part of the branch name.
> This is useless information.

### Examples

Provide a new feature "Awesome Timemachine"


```shell
# ✅ Right
$ git checkout -b feature/awesome-timemachine
```

```shell
# ❌ Wrong
$ git checkout -b feature-awesome-timemachine
$ git checkout -b awesome-timemachine
$ git checkout -b feature-1
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

### Tests

Testing has two main objectives for OpenQDA:

- ensure integrity (system behaves as expected)
- prevent regression (changes/additions/deletions don't cause unintended breaks)

There are a some conventions for testing when contributing:

- make sure your contributions do not break any existing tests
- make sure your changes and fixes are also reflected in new or updated tests
- for new features there should also be respective tests added
- pull requests with broken tests **will not be reviewed until the tests run properly**
- please do not attempt to fix broken tests by using shortcuts, hacks or quirks,
  rather please consult the OpenQDA team for help

Our CI takes care of this check, however you should run tests locally
to see if things break even before committing your changes.

We therefore provide a comprehensive [testing guide](docs/development/backend/testing.md) for you to
run your tests locally.

## Committing your contribution

Once your contribution is ready to be committed you should make
the commit as understandable as possible.

We aim to use [conventional commits](https://www.conventionalcommits.org) where possible.
However, sometimes it's not useful to use them, just because we say so.

Rather keep the following in mind, when writing a commit:

> 1. Can I find my specific commit using `git log` in a year without
> greater effort?
> 2. Can I grasp what the commit is **specifically** about, just by reading the summary?

The above link provides good examples for conventional commits.
However, we also want to provide bad examples, too:

```shell
# ❌ Wrong
$ git commit -a -m "patch-1" # no scope, no action specified
$ git commit -a -m "updated" # no scope, action too generic
$ git commit -a -m "added foobar today" # no scope, useless information
$ git commit -a -m "openqda changed header in preparation page" # useless information, scope too broad
```

We will not fundamentally reject first contributors that violate
the commit conventions, but we will address them in the reviews.

## Pull request and review process

Once all your work is committed you can finally push your branch
to your repository. Using our awesome example branch name this would look
like this:

```shell
$ git push origin feature/awesome-timemachine
```

Once the push was successful you can open a new pull request.
If you have no experience with how pull requests work, you
can read everything up in the 
[GitHub documentation for pull requests](https://docs.github.com/en/pull-requests). 

If the CI passes every workflow we will start a review.
Otherwise, we will ask you to fix the errors from the workflows
before starting any review.

> Keep in mind the goal of the review is to ensure
> quality of the code!

We may or may not address one or more issues with your contribution,
which are entirely related to ensure the quality of the software and
are never intended to offend in any way.

This may include:
- multiple contributions in one pull requests (please avoid this at all costs!)
  causing the pull request to bloat up beyond comprehension
- convention violations
- issues with the solution (for example unsustainable, unscalable etc.)
- code style issues (variable and function names)
- missing documentation and tests
- unclear objectives

If there are no issues or all issues are resolved,
then we will approve the pull request and merge it asap.
You will also be mentioned in the next upcoming release, unless
you explicitly don't want to be named.
