# OpenQDA installation

This guide shows you how to set up and install OpenQDA for **development**.
If you want to install OpenQDA in order to use it in production,
then please follow the [deployment guide](../deployment/deployment.md).
OpenQDA is best developed using a Unixoid operating system, such
as MacOS or one of the many Linux-based distributions.
Windows users should consider using the
[Windows Subsystem for Linux](https://learn.microsoft.com/en-us/windows/wsl/faq).


## Preparations

Before you install OpnQDA, please make sure to decide, whether you want to install
it via Docker or manually.
In any of those two cases, you will have to do the following steps to prepare your
project.

### 1. Install git

In order track changes and contribute to OpenQDA you will have to use git:
https://git-scm.com/

You can theoretically install OpenQDA without Git by downloading it as ZIP folder,
but you will lose any historical information and cannot track any further changes.


### 2. Obtain the source code

If you use git, then you can obtain the code by cloning the repository:

```shell
git clone git@github.com:openqda/openqda.git
```

In case you won't use git you can still [obtain the source code
as ZIP from GitHub](https://github.com/openqda/openqda/archive/refs/heads/main.zip).

### 3. Create and configure the environment file

OpenQDA stores application-wide configuration and credentials
in a special `.env` file, in order to prevent leakage of sensitive credentials
and to ensure flexibility.

In development, you can create one on your own by copying the `.env.example`:

```shell
cd web
cp .env.example .env
```

You will later need to configure the database by editing the `DB_`
entries in `.env` to reflect your database connection details.


## Decide for a Setup

Now it's time to choose your path:

[A - Docker-based setup](./docker.md)

[B - Manual Setup](./manual.md)