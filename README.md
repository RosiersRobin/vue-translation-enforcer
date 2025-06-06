# Vue translation enforcer
Small package that enforces the use of existing translation strings in vue

## Installation

You can install the package via composer:

```bash
composer require robinrosiers/vue-translation-enforcer
```

Install Laravel Vue i18n package:
```bash
npm i laravel-vue-i18n
```

load the plugin in your vite.config.js:

```typescript
import { defineConfig, loadEnv } from "vite";
import i18n from "laravel-vue-i18n/vite";

export default defineConfig({
    plugins: [
        ...
        i18n(),
    ],
});

```

**Bonus:**

If you want to have auto generation during development, add this script to the plugins list:

```typescript
run([
    {
        name: "translations-js",
        run: ["php", "artisan", "vte:export"],
        pattern: ["lang/**/*.php"],
    },
])
```

Create a helper function to 'get' the translations that is based off the laravel-vue-i18n package;

```typescript
import { trans } from "laravel-vue-i18n";
import { TranslationKey } from "../translations/lang-keys";

export const getTrans = (
    key: TranslationKey, // this is the magic
    replace: Record<string, string> = {},
): string => {
    if (key === null || key === trans(key, replace)) {
        return "";
    }
    return trans(key, replace);
};
```

**Add the following to your .gitignore:**

```/resources/js/translations/```

## Usage

To regenerate the translations array, simply run 
```bash
php artisan vte:export
```

## Contributing

Package is open for pull requests!

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Robin Rosiers](https://github.com/robinrosiers)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.