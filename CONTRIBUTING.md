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

## Before contributing

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

## During Contribution

You are now on the right branch and start to work.

### Committing your work

