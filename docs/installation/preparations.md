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

---

# Import / Export Architecture Concept

> **Audience:** Senior software engineers who will implement or review this feature.
> **Status:** Concept / proposal — no UI, no implementation details yet.

## Overview

OpenQDA must be able to move an entire project in and out of the system through five distinct channels:

| Direction | Format | Purpose |
|-----------|--------|---------|
| Export | **Full archive** (ZIP) | Backup, long-term archiving, full-fidelity round-trip |
| Export | **REFI-QDA** (ZIP/XML) | Interoperability with other QDA tools (MaxQDA, ATLAS.ti, …) |
| Export | **Publication data** | Share research artefacts with a selective, curated subset |
| Import | **Full archive** (ZIP) | Restore a backup or migrate between OpenQDA instances |
| Import | **REFI-QDA** (ZIP/XML) | Bring projects in from other QDA tools |

These operations share infrastructure but vary in data touched and transformations required. The architecture must keep the shared parts DRY while allowing each format to vary independently — without turning into a tangle of `if`/`switch` branches.

## Constraints and Non-Functional Requirements

**Resource Impact:** Export/import can span hundreds of source files and thousands of codes, quotations, memos, etc. Naive synchronous execution would block the web process for seconds to minutes, cause long-running DB transactions, and exhaust memory if the entire project graph is loaded at once.

**Consistency:** An export must represent a point-in-time snapshot. An import must be atomic — either the entire project is created or nothing is.

**Extensibility:** New formats must be addable without modifying existing code.

## High-Level Architecture

```
HTTP Layer → Queue (Job) → Pipeline (Pipes) → Format Strategy → Data Access Layer
```

- **HTTP Layer**: validates the request, resolves the right handler via a Registry, dispatches a Queue Job, returns `202 Accepted` with a tracking token.
- **Job Layer**: `ExportProjectJob` / `ImportProjectJob` — owns the lifecycle (acquire lock → execute → release lock).
- **Pipeline Layer**: ordered sequence of `Pipe` classes, each handling one transformation step.
- **Format Strategies**: one class per format implementing `ExporterInterface` / `ImporterInterface`.
- **Data Access Layer**: chunked/lazy Eloquent queries; `Storage` facade for binary files.

## Design Patterns

### Strategy — Format Handlers

```php
interface ExporterInterface {
    public function format(): string;
    public function export(Project $project, ExportContext $context): ExportResult;
}

interface ImporterInterface {
    public function format(): string;
    public function import(string $sourcePath, ImportContext $context): ImportResult;
}
```

Concrete classes: `FullArchiveExporter`, `RefiExporter`, `PublicationExporter`, `FullArchiveImporter`, `RefiImporter`.

> **OCP:** A new format = one new class + one registration. No existing code changes.

### Registry / Factory — Format Resolution

```php
class ExporterRegistry
{
    public function __construct(private readonly iterable $exporters) {}

    public function resolve(string $format): ExporterInterface
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->format() === $format) return $exporter;
        }
        throw new UnsupportedFormatException($format);
    }
}
```

Registered in `ImportExportServiceProvider`. Callers never depend on concrete classes.

### Value Objects — Context and Results

```php
readonly class ExportContext {
    public function __construct(
        public string $format,
        public int    $requestedByUserId,
        public string $outputPath,
        public array  $options = [],
    ) {}
}
```

### Pipeline — Composable Export Steps

```php
app(Pipeline::class)
    ->send($payload)
    ->through([
        CollectProjectMetadataPipe::class,
        SerializeSourceFilesPipe::class,
        SerializeCodesPipe::class,
        SerializeQuotationsPipe::class,
        WriteManifestPipe::class,
        PackageZipPipe::class,
        CleanUpTempFilesPipe::class,
    ])
    ->thenReturn();
```

Each `Pipe` satisfies SRP and can be tested in isolation.

### Template Method (optional)

An abstract `BaseExporter` can encode the skeleton (`prepareTempDir → writeContent → packageOutput → cleanup`) while subclasses supply only `writeContent`. Introduce only when the skeleton is stable; otherwise prefer Pipeline composition.

## Queue Architecture

Both `ExportProjectJob` and `ImportProjectJob` implement `ShouldQueue` and carry only primitive IDs + context DTOs (not loaded models) to avoid serialisation bloat.

```php
class ExportProjectJob implements ShouldQueue {
    public int $timeout      = 600;
    public int $tries        = 1;
    public int $maxExceptions = 1;
    // ...
}
```

**Dedicated queue** (`exports`): export/import jobs never block normal application jobs.

**Atomic locks**: prevent duplicate concurrent exports of the same project in the same format:

```php
$lock = Cache::lock("export:{$projectId}:{$format}", 660);
$lock->block(5);
try { /* export */ } finally { $lock->release(); }
```

## Database Concerns

**Point-in-time consistency:** Wrap the read phase in a `REPEATABLE READ` transaction to guarantee a consistent snapshot without write locks.

**Chunked/Lazy queries:** Use `cursor()` or `chunk()` instead of eager-loading the full relationship graph.

```php
$project->quotations()->cursor()->each(fn ($q) => $writer->writeQuotation($q));
```

**Import atomicity:** Imports run inside a `DB::transaction()`. Any failure triggers a full rollback.

## Proposed Directory Layout

```
app/
├── Contracts/Export/ExporterInterface.php
├── Contracts/Import/ImporterInterface.php
├── DataTransferObjects/{Export,Import}{Context,Result}.php
├── Enums/{Export,Import}Format.php
├── Jobs/{Export,Import}ProjectJob.php
├── Models/ExportRecord.php
├── Providers/ImportExportServiceProvider.php
└── Services/
    ├── Export/{BaseExporter,ExporterRegistry,FullArchiveExporter,RefiExporter,PublicationExporter,Pipes/…}.php
    └── Import/{ImporterRegistry,FullArchiveImporter,RefiImporter,Pipes/…}.php
```

## REFI-QDA Specifics

- Map OpenQDA domain model ↔ REFI entities (`Cases`, `Codes`, `Sets`, `Notes`, `Sources`, `Variables`, …)
- Handle GUID-based references (OpenQDA uses integer IDs)
- Keep mapping in a dedicated `RefiMapper` class for independent testability
- Evaluate [openqda/refi-tools](https://github.com/openqda/refi-tools) before writing custom XML code

## Publication Export

1. Excludes private memos, internal codes, confidential material
2. Applies anonymisation transforms via an injected `AnonymisationService`
3. Output format varies per target (ZIP / JSON / PDF)

## HTTP API Sketch

```
POST /api/projects/{project}/exports   → 202 { export_id, status_url }
GET  /api/exports/{export}             → { status, download_url }
GET  /api/exports/{export}/download    → binary stream
POST /api/projects/{project}/imports   → 202 { import_id, status_url }
GET  /api/imports/{import}             → { status, errors }
```

Protected by `ExportPolicy` / `ImportPolicy`.

## Testing Strategy

| Layer | What to test | Tool |
|-------|-------------|------|
| Pipe classes | Each pipe with a fake payload | PHPUnit unit test |
| Exporter/Importer | Round-trip: export → import → assert equality | Feature test + `RefreshDatabase` |
| REFI mapper | Mapping fidelity against known REFI fixture files | Unit test |
| Jobs | Dispatch, lock contention, failure handling | `Queue::fake()` |
| Registry | Format resolution, exception on unknown format | Unit test |

## Dependency Evaluation

| Dependency | Justification | Decision |
|-----------|--------------|---------|
| `openqda/refi-tools` | REFI XML schema handling | ✅ Evaluate first |
| `maatwebsite/excel` | Spreadsheet publication export | ⬜ Defer |
| `barryvdh/laravel-dompdf` | PDF publication export | ⬜ Defer |

No new dependencies are required for the full-archive format. PHP's `ZipArchive` and Laravel's `Storage` facade are sufficient.

## Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| **Strategy** for format handlers | OCP: new format = new class, no existing changes |
| **Registry** (not switch) | Couples concrete classes only in the ServiceProvider |
| **Pipeline** within handlers | SRP: each step composable, reorderable, testable |
| **Queue jobs** for all I/O | Web processes never block; retry/failure visibility |
| **Atomic lock** per project+format | Prevents duplicate exports without a mutex table |
| **Chunked DB reads** | O(1) memory regardless of project size |
| **DB transaction for imports** | Atomicity — no partial state |
| **DTOs** for context/results | Type-safe, no stringly-typed arrays between layers |
| **Enums** for formats | Compile-time safety; no raw strings in dispatch calls |
```

You will later need to configure the database by editing the `DB_`
entries in `.env` to reflect your database connection details.


## Decide for a Setup

Now it's time to choose your path:

[A - Docker-based setup](./docker.md)

[B - Manual Setup](./manual.md)