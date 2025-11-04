# 2023OrangePeels Modules

This directory collects framework-agnostic services that can be reused across projects. Each package keeps its implementation in a `src/` directory with supporting subfolders for configuration, interfaces, and domain specifics. Use the following guide to understand what lives where before wiring a module into your application.

## Notable Modules

### acl/src
- `Acl.php`, `User.php`: Core ACL service plus session-aware user helper.
- Subfolders: `config/` (default settings), `models/` (DB accessors), `entities/` (user/role/permission value objects), `interfaces/`, `exceptions/`.

### asset/src
- `Asset.php`, `Priority.php`: Asset registration and priority queueing.
- Subfolders: `config/`, `interfaces/`, `exceptions/` for integration contracts and error handling.

### auth/src
- `Auth.php`, `AuthInterface.php`: Simple credential validation service with configurable column names.
- Subfolder: `config/` holding default error messages and table mappings.

### benchmark/src
- `Benchmark.php`: Micro-level timing and memory measurement helpers.

### cache/src
- `FilesCache.php`, `RedisCache.php`, `MemcachedCache.php`, `IncludeCache.php`, `DummyCache.php`: Cache adapters covering multiple backends.

### collector/src
- `Collector.php`: Event data collector/aggregator.
- Supporting files: `CollectorInterface.php`, `CollectorException.php`.

### console/src
- `Console.php`, `ConsoleInterface.php`: CLI dispatcher utilities.
- Subfolders: `config/` for command registration, `exceptions/` for console-specific errors.

### cookie/src
- `Cookie.php`, `CookieInterface.php`: Cookie lifecycle and configuration helpers.
- Subfolder: `config/` (defaults for security and domain handling).

### disc/src
- `Disc.php`: Filesystem synchronisation/management entry point.
- Subfolders: `disc/` (utility classes for import/export and file helpers), `exceptions/`.

### fig/src
- `fig.php`, `FigException.php`: Lightweight view plugin loader.
- Subfolder: `figs/` containing bundled plugins.

### flashmsg/src
- `Flashmsg.php`, `FlashmsgInterface.php`: Flash messaging with configurable transports.
- Subfolder: `config/` (message defaults and namespaces).

### handlebars/src
- `Handlebars.php`, `HandlebarsView.php`, `HandlebarsPluginCacher.php`: Handlebars view integration with plugin caching.
- Subfolders: `config/`, `hbsPlugins/` (extension points), `exceptions/`.

### language/src
- `Language.php`, `LanguageInterface.php`: Translation and localisation service.

### mergeview/src
- `MergeView.php`, `Merge.php`: View composition/merging utilities.
- Subfolder: `exceptions/` for merge-specific errors.

### model/src
- `Model.php`, `Crud.php`, `Sql.php`, `StringBuilder.php`: Database abstraction helpers.
- Subfolders: `config/` (default connection/decorator options), `exceptions/`.

### negotiate/src
- `Negotiate.php`, `NegotiateInterface.php`: HTTP content negotiation helpers (accept headers, formats).

### observer/src
- `Server.php`, `Client.php`: Observer pattern implementation for inter-service messaging.

### quickView/src
- `QuickView.php`: Minimal view rendering helper for rapid output.

### session/src
- `Session.php`, `SessionInterface.php`: Session wrapper with configurable storage behaviour.

### snippets/src
- `Snippet.php`, `SnippetInterface.php`, `SnippetException.php`: Reusable HTML/text snippet registry.

### stash/src
- `Stash.php`, `StashInterface.php`, `StashException.php`: Simple storage abstraction for transient data.

### validate/src
- Core classes: `Validate.php`, `Filter.php`, `Remap.php`, `ValidJson.php`, `WildNotation.php`, `Notation.php`, `ValidationError.php`.
- Subfolders: `config/` (rule sets), `interfaces/`, `rules/` (built-in validators), `exceptions/`.
