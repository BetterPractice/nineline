# Using NineLine on a roadmap PHP build

NineLine depends on language features (value structs, monomorphized generics,
extension methods, modules) that exist only on the **`holly-roadmap`** branch of
php-src. This guide takes you from a fresh checkout of that branch to calling
NineLine through Composer.

> **Two PHPs, one job.** It helps to keep two roles separate: the PHP that *runs
> Composer* (any build with the `phar` extension — your existing system PHP is
> fine) and the PHP that *runs your code* (the roadmap build, which has the
> language features). They do not have to be the same binary.

---

## 1. Build the roadmap PHP

```sh
git clone https://github.com/hollyschilling/php-src.git
cd php-src
git checkout holly-roadmap

./buildconf --force
./configure \
    --enable-cli \
    --enable-phar \
    --with-openssl \
    --with-curl \
    --enable-mbstring \
    --enable-zip
make -j"$(getconf _NPROCESSORS_ONLN)"

sapi/cli/php -v      # should report: PHP 9.0.0-dev
```

Notes:

- The branch reports **`9.0.0-dev`**, which satisfies NineLine's `"php": ">=9.0"`.
- `--enable-phar`, `--with-openssl`, and `--with-curl` let you run **Composer
  directly under this binary** (Setup B below). Omit them and use Setup A instead.
- `--enable-mbstring` is optional — it activates NineLine's character-aware string
  methods (`length()`, `truncate()`, `upperMb()`, `lowerMb()`); everything else
  works without it.
- On macOS you may need to point at Homebrew libraries, e.g.
  `--with-openssl=$(brew --prefix openssl)` and `PKG_CONFIG_PATH` for curl.

---

## 2. Install NineLine via Composer

Pick **one** of the two setups.

### Setup A — run Composer under your system PHP (simplest)

Your existing PHP (Homebrew, distro, etc.) already has `phar`, so it can run
Composer. Because that PHP is older than 9.0, declare the **target** platform so
the `>=9.0` requirement resolves; the code will actually run under the roadmap
binary later.

```sh
cd my-demo-app
composer config platform.php 9.0.0        # resolve as if on PHP 9.0
composer require betterpractice/nineline
```

Then always **run** your code with the roadmap binary:

```sh
/path/to/php-src/sapi/cli/php public/index.php
```

### Setup B — run Composer under the roadmap PHP

If you built with `--enable-phar --with-openssl --with-curl`, run Composer with
the roadmap binary and skip the platform override entirely:

```sh
cd my-demo-app
/path/to/php-src/sapi/cli/php /usr/local/bin/composer require betterpractice/nineline
/path/to/php-src/sapi/cli/php public/index.php
```

### If NineLine is not on Packagist yet

Add it as a VCS (or `path`) repository in the consuming project's `composer.json`
before requiring it:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/BetterPractice/nineline" }
    ],
    "require": {
        "betterpractice/nineline": "^0.1"
    }
}
```

Use a tagged version (`^0.1`) if one exists, or `dev-main` with
`"minimum-stability": "dev"` in your root `composer.json` if you are tracking the
branch directly.

> `bootstrap.php` in this repository is only for running NineLine's own test
> suite **without** Composer. As a consumer you use Composer's
> `vendor/autoload.php`; you do not need `bootstrap.php`.

---

## 3. Use it — mind the `use module` compile-timing rule

`use module … as NL;` is resolved when the file is **compiled**, so the module
must already be registered at that point. Composer registers it (via the `files`
autoload entry) when `vendor/autoload.php` runs. The consequence:

- ✅ Use NineLine from **autoloaded classes** — a controller, service, model.
  They compile lazily, long after the autoloader has run, so the module is ready.
- ✅ Or from a file you `require` **after** `vendor/autoload.php`.
- ❌ Do **not** put `use module …` at the top of the same entry script that
  requires the autoloader — that script is compiled in full before its first line
  executes, so the module is not registered yet.

### Framework-native (recommended)

```php
namespace App\Services;

use module BetterPractice\NineLine as NL;   // fine: this class autoloads after bootstrap

final class TagCloud
{
    /** @return NL:>Sequence<string> */
    public function normalize(string $csv): NL:>Sequence<string>
    {
        return $csv->split(",")
            ->map<string>(fn(string $t) => $t->trimmed()->lower())
            ->filter(fn(string $t) => $t !== "");
    }
}
```

### Plain-script demo (note the split into two files)

```php
// index.php — the entry script. NO `use module` here.
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/demo.php';   // compiled now, module already registered
```

```php
// demo.php
use module BetterPractice\NineLine as NL;

$seq = new NL:>Sequence<int>([3, 1, 2]);
$seq->sort();
echo implode(",", $seq->toArray()), "\n";                         // 1,2,3

echo "a,b,c"->split(",")->map<string>(fn($w) => strtoupper($w))->count(), "\n";  // 3

$port = NL:>Result<int, string>::ok(8080);
echo $port->unwrapOr(80), "\n";                                   // 8080
```

```sh
/path/to/php-src/sapi/cli/php index.php
```

---

## Autoloader notes

- The **standard** Composer autoloader works out of the box: PSR-4 resolves the
  structs and extensions on demand, and the `files` entry registers the module.
  Composer does **not** tokenize NineLine's source in this mode, so the new
  syntax never has to pass through Composer's parser.
- Avoid `composer install -o` / `--classmap-authoritative` unless your Composer
  uses a `composer/class-map-generator` that understands extension declarations
  (PR #43) **and** the dump runs under a PHP whose tokenizer knows the new
  syntax. The non-authoritative autoloader has no such requirement.

---

## Troubleshooting

| Symptom | Cause & fix |
|---------|-------------|
| `PHP's phar extension is missing. Composer requires it` | You ran Composer under the roadmap build, which lacks `phar`. Use Setup A (system PHP), or rebuild with `--enable-phar`. |
| `betterpractice/nineline … requires php >=9.0 … your php version (8.x.y) does not satisfy` | Composer is resolving against an older PHP. Run `composer config platform.php 9.0.0` (Setup A), run Composer under the 9.0-dev build (Setup B), or `composer require … --ignore-platform-req=php`. |
| Parse/`Cannot access … module …` error on a line with `use module` | That file was compiled before `vendor/autoload.php` ran. Move the `use module` code into an autoloaded class or a file `require`d after the autoloader. |
| `Call to a member function length() on string` | `ext-mbstring` is not loaded, so `StringMbExtensions` is not declared. Rebuild with `--enable-mbstring`, or use the mbstring-free methods (`byteLength()`, etc.). |
