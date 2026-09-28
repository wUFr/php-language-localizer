# Security Policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 2.x     | Yes       |
| 1.x     | No. Upgrade to 2.x, see [UPGRADE.md](UPGRADE.md) |

## Reporting a vulnerability

Please do not open a public issue. Report it privately instead:

- through GitHub private vulnerability reporting (**Security → Report a vulnerability** in this repository), or
- by e-mail to packages@jiribelsky.cz.

Include the affected version, a minimal example and the impact you expect.

## What the library guarantees

- **File access stays inside the locale directories.** File and language names are validated before any path is built. Names with `..`, backslashes, absolute paths, stream wrappers or other unexpected characters are rejected and reported as missing translations. This holds even when the language comes from a visitor.
- **Parameters are HTML-escaped by default.** Values passed to `locale()` are escaped with `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` unless you mark them raw (`Raw`, `_raw`) or disable escaping.
- **Placeholders are replaced in a single pass,** so a value cannot inject the content of another parameter.
- **Missing translations do not reflect markup.** The default output is the escaped `file.key` identifier.
- **Locale files run in an isolated scope** and cannot reach the translator instance or the call's parameters.

## What stays your responsibility

- **Locale files are PHP code.** Only load locale directories you control. Never let users upload or edit files inside them.
- **Translation text is trusted markup.** It is output as written. If translators you do not trust edit your locale files, review their changes like code.
- **Raw values are not escaped.** Only mark values raw when you built the markup yourself, and escape any user data inside it.
- **Escaping targets HTML text and quoted attributes.** For other contexts (JavaScript, CSS, URLs, unquoted attributes) encode the output for that context yourself.
- **A custom missing-translation handler** must escape anything it builds from the file, key or detail it receives.
