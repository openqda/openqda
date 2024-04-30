# OpenQDA

[![Project Status: WIP – Initial development is in progress, but there has not yet been a stable, usable release suitable for the public.](https://www.repostatus.org/badges/latest/wip.svg)](https://www.repostatus.org/#wip)
![This is a research software](https://img.shields.io/badge/research-software-blue?style=plastic)

> **Important note**
>
> OpenQDA is still under development and there are many parts
> left out or still in discussion!
>
> See our [development roadmap](https://github.com/openqda/openqda/milestones)
> for ongoing and upcoming features and fixes.

## About

OpenQDA is a sustainable, free/libre Open Source Software for collaborative qualitative research.
OpenQDA is developed by the ZeMKI (Centre for Media, Communication and Information Research) at the
University of Bremen, and it's main instance (https://openqda.org)
is hosted on servers integrated in the university's infrastructure.

If you are a user and want to learn OpenQDA, then we advise you to read
the [user documentation](https://openqda.github.io/user-docs/).

If you are still unsure about what OpenQDA is or does, then please [consult our FAQ](https://openqda.org/faq).

## Roadmap

We are constantly updating our [development roadmap](https://github.com/openqda/openqda/milestones)
in regard to the upcoming releases.

## Development

If you have reached this section, chances are high your either want one of the following:

- run OpenQDA on your own infrastructure
- understand OpenQDA or hack a local version of OpenQAD
- improve OpenQDA
- learn research software engineering with OpenQDA as an example project

### Architecture overview

In terms of software architecture OpenQDA is a pluggable monolith. That means,
there is a core application that can be extended by internal or external programs
that follow a certain interface definition.

A plugin can therefore be a php module (backend), a JavaScript module or Vue component (frontend)
or an external service (both).

One of the main goals of this architecture is to make it easy for you to extend OpenQDA
without the need to know its exact internals but by following only a few rules that
a plugin must comply with.

### Core

If you are interested in working on the OpenQDA core then please consult our
[core development guide](./docs/CORE.md).

### Plugins

If you are interested in developing your own plugin then please consult our
[plugin development guide](./docs/PLUGINS.md).

### Deployment

We cover aspects of deployment (installing/updating OpenQDA on your staging or production servers)
in a separate [deployment guide](./docs/DEPLOYMENT.md).

## Publications

We provide a [citation file](./CITATION.cff) to enable citations of this software.
OpenQDA is entirely free, so we encourage you to

## Credits

The "aTrain" plugin for transcription is developed and licensed by Armin Haberl, Jürgen Fleiß,
Dominik Kowald, Stefan Thalmann and is published under

> Haberl, A., Fleiß, J., Kowald, D., Thalmann, S., 2023.
> “Take the aTrain. Introducing an Interface for the Accesible Transcription of Interviews.”,
> University of Graz, School of Business, Economics and Social Sciences Working Paper 2023-02.

Please note, that if you use the auto-transcription feature in OpenQDA then you must
cite their work in your publication under certain conditions.
Please [read their license](https://github.com/JuergenFleiss/aTrain/blob/main/LICENSE) for this.

## Licenses

OpenQDA is a sustainable, free/libre Open Source Software for collaborative qualitative research.
Copyright (C) 2024 ZeMKI, Universität Bremen

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.

The core software of this project is released under the APGL-3.0 license,
which you can read in our [license file](./LICENSE).

Plugins (which includes services) may be distributed under a different license.
Please see their own license files in their respective directories
