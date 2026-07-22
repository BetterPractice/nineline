# NineLine

A PHP standard library built to show off the PHP 9 roadmap features — **value
structs**, **monomorphized generics**, **extension methods**, and **modules** —
composed into an ergonomic, type-safe toolkit that plain PHP cannot express.

```php
use module BetterPractice\NineLine as NL;

// Generic, value-semantic collections — real runtime element types:
$nums = new NL:>Sequence<int>([3, 1, 2]);
$nums->push(4);                       // mutates in place (copy-on-write protects any copy)
$nums->push("oops");                  // TypeError: Argument #1 must be of type int

// Scalar extension methods, activated by the one `use module` line:
"héllo"->byteLength();                // 6   (bytes)
"héllo"->length();                    // 5   (characters — with ext-mbstring)
(42)->isEven();                       // true

// Extensions hand back the library's own collection types, so they compose:
"a,b,c"->split(",")->map<string>(fn($w) => strtoupper($w))->toArray();   // ['A', 'B', 'C']
(1)->upTo(100)->filter(fn($n) => $n % 7 === 0)->count();         // 14

// Value-struct monads:
NL:>Option<int>::some(41)->map(fn($n) => $n + 1)->getOr(0);   // 42
NL:>Result<int, string>::err("bad")->unwrapOr(0);            // 0

// A struct that IS an Iterator — foreach takes the value route, so the
// range is never consumed and can be iterated again:
foreach (new NL:>Range(0, 10, 2) as $i) { /* 0 2 4 6 8 */ }

// Type-changing map (generic method) and typed delegates (variadic pack):
$labels = (new NL:>Sequence<int>([1, 2, 3]))->map<string>(fn(int $n) => "#$n");
$add = new NL:>Func<int, int, int>(fn(int $a, int $b): int => $a + $b);   // (int,int): int
$add->invoke(2, 3);   // 5   — and construction rejects a mismatched callable
```

## Requirements

- PHP built from the `holly-roadmap` branch of php-src (structs, generics,
  extension methods, and the module pattern must all be present).
- `ext-mbstring` is **optional** (a Composer `suggest`): install it to gain the
  character-aware string methods, omit it and the rest of the library is
  unaffected (see *Optional dependencies* below).

There is no build step: `use module BetterPractice\NineLine` activates the
collections *and* the scalar extension methods in one line.

## The module

Everything ships behind a single module, `BetterPractice\NineLine`:

```php
use module BetterPractice\NineLine as NL;   // members + all extensions, one import

$seq = new NL:>Sequence<int>();             // members reached through the :> operator
"a,b,c"->split(",");                        // extensions active for the whole file
```

`use module` both makes the members reachable through `NL:>` **and** activates the
exported scalar extensions — no separate `use extension` lines.

## What's inside

### Collections — generic value structs

| Type | Description |
|------|-------------|
| `Sequence<T>` | An ordered list, iterable with `foreach`. Mutating methods (`push`, `pop`, `shift`, `unshift`, `set`, `removeAt`, `sort`, `reverse`, `append`, `clear`) update the value in place; functional methods (`filter`, `map<U>`, `reduce`, `reversed`) and withers (`withAppended`, `withoutLast`) return new values. `T` is runtime-enforced. |
| `Map<V>` | A string-keyed dictionary, iterable with `foreach`: `set`, `get`, `getOr`, `has`, `remove`, `keys`, `values`, `each`, `mapValues<W>`, `filter`. `V` is runtime-enforced. |

The element type is runtime-enforced **including at construction** — the
constructor routes every element through the typed `push()`/`set()`, so
`new Sequence<int>(['a'])` throws a `TypeError` immediately rather than letting a
wrongly-typed element hide until it fails a `?T` return on later access. The
constructors accept any `iterable` (arrays, generators, another collection).

Because these are `struct`s, they have **value semantics**: a copy is
independent, and copy-on-write means a snapshot is never disturbed by a later
mutation of the original.

```php
$a = new NL:>Sequence<int>([1]);
$a->push(2);
$b = $a;          // a copy
$b->push(3);
$a->toArray();    // [1, 2]     — untouched
$b->toArray();    // [1, 2, 3]
```

### Support — value-struct monads

| Type | Description |
|------|-------------|
| `Option<T>` | `Some(T)` or `None`: `some`, `none`, `fromNullable`, `isSome`, `get`, `getOr`, `getOrElse`, `map`, `filter`, `ifSome`. |
| `Result<T, E>` | `Ok(T)` or `Err(E)`: `ok`, `err`, `isOk`, `unwrap`, `unwrapOr`, `unwrapErr`, `map`, `mapErr`, `match`. Both type arguments are enforced. |
| `Range` | A half-open `int` range implementing the colored `Iterator` (`start`, `end`, `step`, `count`, `contains`, `toArray`). `foreach` takes the value route, so iterating never consumes the range. |
| `Func<...TArgs, TReturn>` | A strongly-typed function delegate (C#-style). The wrapped callable's signature is validated against the type arguments **at construction**; `invoke`/`__invoke`/FCC enforce the return type. Is itself `callable`. |
| `Action<...TArgs>` | A strongly-typed void delegate. Same construction-time signature validation as `Func`. |

`Func`/`Action` are type-safe at **both** ends. At **construction**, the wrapped
callable is reflected and its declared signature checked against the delegate's
type arguments — an incompatible arity, parameter type, or return type throws a
`TypeError` immediately (an untyped closure is accepted and deferred). At
**invocation** (`invoke`, `$f(...)` via `__invoke`, or an FCC `$f->invoke(...)`)
the return value is enforced as `TReturn`. Recovering the type arguments at
runtime relies on `ReflectionClass::getGenericTypeArguments()`.

```php
$toPrice = new NL:>Func<Order, Price>(fn(Order $o): Price => new Price($o->cents / 100));
$toPrice($order);                                    // Price (also usable as any callable)
new NL:>Func<int, string>(fn(int $n): int => $n);    // TypeError: return type (int) is not string
```

### Extensions — methods on the built-in scalars

- **`string`** — `byteLength`, `isEmpty`, `isBlank`, `contains`, `startsWith`,
  `endsWith`, `upper`, `lower`, `capitalize`, `titleCase`, `reverse`, `trimmed`,
  `collapseWhitespace`, `wordCount`, `repeat`, `slug`, `ensurePrefix`,
  `ensureSuffix`, and `split` / `tokenize` / `lines` → **`Sequence<string>`**.
  **With `ext-mbstring`:** `length`, `truncate`, `upperMb`, `lowerMb` (see
  *Optional dependencies*).
- **`int`** — `isEven`, `isOdd`, `abs`, `clamp`, `times`, `pow`, `gcd`,
  `isPrime`, and `upTo` / `downTo` → **`Sequence<int>`**.
- **`array`** — `isEmpty`, `first`, `last`, `sum`, `product`, `contains`,
  `unique`, `flatten`, `mapValues`, `filterValues`, `min`, `max`.

Methods that produce a list of a **concrete** element type return a
`Sequence<…>` rather than a bare `array`, so results flow straight into the
collection API (`->map()`, `->filter()`, `->count()`, `foreach`). Where the
element type is a type parameter (`Map::values()` → `list<V>`) or `mixed`
(`int::times()`, the `array` helpers), a plain `array` is returned — a
`Sequence<V>` cannot be built, since a type parameter may not be used as a
generic argument in this version.

Extension methods bind their receiver to a declared variable (`$s`, `$n`, `$a`)
— there is no `$this` — and it is bound by value, so they never mutate the
caller's scalar. Integer literals need parentheses to be a receiver:
`(42)->isEven()`.

## How the language features are used

- **Value structs** give the collections and monads copy-on-assign semantics with
  no defensive cloning. `mutating` methods update in place under copy-on-write;
  uncolored methods and withers return modified copies (`return $this`).
- **Monomorphized generics** make `Sequence<int>`, `Option<string>`, and
  `Result<int, string>` distinct types whose type arguments are enforced by the
  ordinary typed-property/parameter machinery — the error message names the
  substituted type.
- **Extension methods** add a scalar vocabulary (`"…"->slug()`) without touching
  the engine's string/int/array types, and each file opts in.
- **Modules** bundle the whole surface behind one `use module`, gate member
  names, and activate the exported extensions.

### Generics features and limitations this library works within

Generics support **generic methods** and **variadic type-parameter packs**, which
this library uses directly:

- **Type-changing map is a generic method.** `Sequence<T>::map<U>(): Sequence<U>`
  and `Map<V>::mapValues<W>(): Map<W>` take the target type explicitly at the call
  site (`$seq->map<string>(...)`), and the output type is enforced as each value
  is collected. `Option::map`/`Result::map` stay same-type withers.
- **`Func`/`Action` use a variadic pack** of type parameters
  (`Func<...TArgs, TReturn>`). A pack requires **at least one element**, so these
  model delegates of one or more arguments (a zero-argument supplier is not
  expressible via a pack).

Two restrictions remain, and the API is shaped around them:

- A **type parameter may not be used as a generic argument** — `Sequence<V>`
  cannot appear inside `Map<V>`'s body, so `Map::values()` returns `list<V>` and
  `append()` takes `iterable` (re-enforcing `T` through `push()`) rather than
  `Sequence<T>`.
- Methods returning "the same instantiation" are typed **`static`** (the
  late-static-bound `Sequence<int>`), because `self` in a type position names the
  bare template.

## Optional dependencies via conditional extensions

`ext-mbstring` is a *suggested*, not required, dependency. The methods that need
it — `length`, `truncate`, `upperMb`, `lowerMb` — live in a separate extension,
[`StringMbExtensions`](src/Extensions/StringMbExtensions.php), whose whole
declaration is wrapped in a runtime guard:

```php
if (extension_loaded('mbstring')) {
    extension StringMbExtensions on string $s {
        public function length(): int { return mb_strlen($s); }
        // …
    }
}
```

Because extension registration is **load-gated** — it happens when the
`extension { }` declaration executes — guarding the declaration makes the methods
exist only when their dependency does. Without mbstring the file still autoloads
on first use, the declaration is skipped, and `"x"->length()` raises the ordinary
*"Call to a member function length() on string"* — the method is simply not
there, rather than present-but-broken. This is the general recipe for
**capability-gated extension methods**: split the dependent methods into their own
named extension and guard its declaration.

### A struct gotcha worth knowing

Array builtins that take their array **by reference** (`sort`, `array_pop`,
`array_shift`, `array_unshift`, `array_splice`) cannot receive a struct property
directly — the interior-reference ban forbids it. Operate on a local copy and
assign it back:

```php
public function pop() mutating: ?T {
    $items = $this->items;      // local copy
    $item  = array_pop($items); // by-ref is fine on a local
    $this->items = $items;      // assign back
    return $item;
}
```

## Running the tests

```
php tests/run.php
```

The suite is plain PHP with a tiny assertion harness (`tests/harness.php`); each
case under `tests/cases/` imports the module and asserts against the php-src
binary. When `ext-mbstring` is loaded the character-aware methods are asserted
directly; when it is absent, the suite instead asserts that those methods are
correctly *gated off* (calling them raises an `Error`).

## Engine issues surfaced while building (holly-roadmap)

Building this library surfaced two integration gaps between features. Both were
**fixed at the source**; minimal repros are kept in the development scratchpad.

1. **`Module:>Template<Args>` gating** — the `:>` module-resolution operator did
   not cover *stamped generic instantiations*, so `NL:>Sequence<int>` hit the
   module acquisition gate. Fixed.
2. **Generic-instantiation scope in internal dispatch** — when `foreach` (or any
   internal call: `IteratorAggregate::getIterator`, an `Iterator`'s
   `rewind`/`next`/`current`) invoked a method on a **stamped instantiation**, the
   call ran with the *template's* scope instead of the instantiation's, so
   `protected`/`private` access was denied (only `public` slipped through). Direct
   `$obj->method()` calls were unaffected. Fixed — `Map<V>`, `Range`, and
   `foreach` over `Sequence`/`Map` all work as a result.

## Layout

```
src/
  NineLine.php              module definition (exports members + extensions)
  Collections/Sequence.php  struct Sequence<T>
  Collections/Map.php       struct Map<V>
  Support/Option.php        struct Option<T>
  Support/Result.php        struct Result<T, E>
  Support/Range.php         struct Range implements Iterator, Countable
  Extensions/               StringExtensions, StringMbExtensions (mbstring-gated),
                            IntExtensions, ArrayExtensions
tests/
  run.php  harness.php  cases/
bootstrap.php               PSR-4 autoloader + module registration (no Composer needed)
composer.json               PSR-4 + files autoload for a real install
```
