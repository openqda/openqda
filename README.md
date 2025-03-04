<div align="center">
    <h2>OpenQDA</h2>
    <p>Collaborative Qualitative Research</p>

[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)
![This is a research software](https://img.shields.io/badge/research-software-blue?style=plastic)
[![DOI](https://zenodo.org/badge/793524421.svg)](https://zenodo.org/doi/10.5281/zenodo.11195871)
[![Backend Tests](https://github.com/openqda/openqda/actions/workflows/backend_tests.yml/badge.svg)](https://github.com/openqda/openqda/actions/workflows/backend_tests.yml)
[![Client Tests](https://github.com/openqda/openqda/actions/workflows/client_tests.yml/badge.svg)](https://github.com/openqda/openqda/actions/workflows/client_tests.yml)
[![CodeQL](https://github.com/openqda/openqda/actions/workflows/github-code-scanning/codeql/badge.svg)](https://github.com/openqda/openqda/actions/workflows/github-code-scanning/codeql)
[![Deploy Docs](https://github.com/openqda/openqda/actions/workflows/deploy_docs.yml/badge.svg)](https://github.com/openqda/openqda/actions/workflows/deploy_docs.yml)
</div>


<p align="center">

<img src="https://raw.githubusercontent.com/openqda/.github/main/profile/bg_top_left_512x512_96dpi.PNG" alt="img data collection" width="200"/>
<img src="https://raw.githubusercontent.com/openqda/.github/main/profile/bg_bottom_left_512x512_96dpi.PNG" alt="img data preparation" width="200" />
<img src="https://raw.githubusercontent.com/openqda/.github/main/profile/bg_top_right_512x512_96dpi.PNG" alt="img coding" width="200"/>
<img src="https://raw.githubusercontent.com/openqda/.github/main/profile/bg_bottom_right_512x512_96dpi.PNG" alt="img analysis" width="200"/>
</p>
<p align="center">
    <img src="https://github.com/openqda/.github/blob/main/profile/zemki-und-uni-logo-weiss-1x.png?raw=true" alt="img ZeMKI" width="200"/>
</p>

### Quick links

- 🚀 Register now: https://openqda.org
- 📔 Read the user-docs: https://openqda.github.io/user-docs/
- 📢 Give feedback and join the discussion: https://github.com/openqda/feedback
- 📧 Contact us: [openqda@uni-bremen.de](mailto:openqda@uni-bremen.de)

## What is OpenQDA?

OpenQDA is a sustainable, free/libre Open Source Software for collaborative qualitative data analysis.

It's developed by the [ZeMKI (Centre for Media, Communication and Information Research)](https://zemki.uni-bremen.de/)
at the [University of Bremen](https://www.uni-bremen.de/).

It's publicly available under https://openqda.org and is hosted on servers,
integrated in the university's infrastructure.

> If you are a user and want to learn OpenQDA, then we advise you to read
> the [user documentation](https://openqda.github.io/user-docs/).

If you are still unsure about what OpenQDA is or does, then please [consult our FAQ](https://openqda.org/faq).

## Publications and Citation

### Citation

We provide a [citation file](./CITATION.cff) to enable automated citations of this work.

Note: Every release obtains a DOI from Zenodo and there is also a base DOI for the project as a whole,
which will always redirect to the latest current release: https://doi.org/10.5281/zenodo.11195871

If you prefer manual citation, then please use the following citation example (APA style):

```
Belli, A., Küster, J., Matayeva, L., Hohmann, F., Sinner, P., Krüger, G., Wolf, K., & Hepp, A. (2025). OpenQDA (1.0.0). Zenodo. https://doi.org/10.5281/zenodo.14772936
```

### Third-Party Citation

The "aTrain" plugin for transcription is developed and licensed by Armin Haberl, Jürgen Fleiß,
Dominik Kowald, Stefan Thalmann and is published under

> Haberl, A., Fleiß, J., Kowald, D., Thalmann, S., 2023.
> “Take the aTrain. Introducing an Interface for the Accesible Transcription of Interviews.”,
> University of Graz, School of Business, Economics and Social Sciences Working Paper 2023-02.

Please note, that if you use the auto-transcription feature in OpenQDA then you must
cite their work in your publication under certain conditions.
Please [read their license](https://github.com/JuergenFleiss/aTrain/blob/main/LICENSE) for this.

## Roadmap

We are constantly updating our [development roadmap](https://github.com/openqda/openqda/milestones) 
in regard to the upcoming releases.

## Development

If you have reached this section, chances are high your either want one of the following:

- run OpenQDA on your own infrastructure
- understand OpenQDA or hack a local version of OpenQAD
- improve OpenQDA
- learn research software engineering with OpenQDA as an example project

### Developer Documentation

We provide an extensive developer documentation in the `/docs` folder.
Here are the quick links to the guides:

- [tech stack](docs/introduction/tech-stack.md)
- [architecture overview](docs/introduction/architecture.md)
- [installation guide](./docs/INSTALLATION.md)
- [core development](./docs/CORE.md)
- [plugin development](./docs/PLUGINS.md)
- [deployment guide](docs/deployment/deployment)

### API Docs

In addition to the developer guides above, we also provide API docs:

- [client api docs](docs/api/client)
- backend api docs (coming soon!)

## Licenses

OpenQDA is a sustainable, free/libre Open Source Software for collaborative qualitative research.
Copyright (C) 2024 ZeMKI, Universität Bremen

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.

The core software of this project is released under the APGL-3.0 license,
which you can read in our [license file](./LICENSE).

Plugins (which includes services) may be distributed under a different license.
Please see their own license files in their respective directories
