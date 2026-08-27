# About TYPO3 Tonictypes Core Extension

Build easy and intuitive TCA-powered records with customizable backend forms on the fly without any programming knowledge.
You can create datatypes and select from a list of plugins to display your created records.

This extension is a easy to use data structure builder that saves you a lot of time, because there is no more need of creating an extension for
every need. Great solutions can be done by just a few clicks and fluid templating.

You only need one extension to create a lot of solutions.

Having just one extension to maintain gives you the advantage to
improve your workflows when upgrading TYPO3 versions.

Everything is fully compatible with the TYPO3 core.

## Documentation

Official manual (for [docs.typo3.org](https://docs.typo3.org/)):

- Source: [`Documentation/`](Documentation/) in this repository
- Published: [https://docs.typo3.org/p/k3n/tonictypes/main/en-us/](https://docs.typo3.org/p/k3n/tonictypes/main/en-us/)

Release notes: see [CHANGELOG.md](CHANGELOG.md)

## Installation

We recommend installing the extension using [Composer](https://getcomposer.org):

    composer require k3n/tonictypes

## Compatibility

+ TYPO3 CMS 12.4 – 14.9
+ PHP 8.2 – 8.5

## Professional

We are also creating a Tonictypes Professional Version, which helps us to provide additional features by
supporting our development. The Tonictypes Professional Extension is in a continuous process of getting
new features such as plugins and more configuration options, to improve your workflows even more.

Additional fields and components are also available for specific usage like

- API Building
- Creating Blog-like Content within your records
- Combining information and building html fields in the TYPO3 Backend
- Advanced field types (DynamicInput, Inline, Flex, PassThrough, and more)
- and many more

Please refer to this url:

[https://t3planet.de/tonictypes](https://t3planet.de/tonictypes)


## Highlights

+ Create customized records out of datatypes on the fly
+ Dynamic Configuration of the Plugins to get nearly every solution
+ No extension programming needed
+ Inject dynamic variables of different types to your fluid templates
+ Easy fluid templating with intuitive customizable variable naming
+ Export and import datatype structures (fields, variables, table schema)
+ Predefined datatype import via dashboard widget
+ Optional default-hidden setting for new datatype records
+ Backend Toolbar Item for easy record management (Professional)
+ Language support

## Workflow

1. Create Fields for your custom record that you assign later to a datatype
2. Create a datatype and assign the fields, that you've created before.
3. Create your records
4. Create fluid templates for the records. You can create lists or single views.
5. Insert Record-Plugin to your site to display record(s)

Optional helpers:

- Use **Dashboard > Predefined Datatype Import** to load the bundled sample datatype
- Use **System > Export / Import** to export or import datatype structures between instances

## Configuration

Tonictypes can be activated in two ways. Use either one or combine them (see mixed setup below).

### Site sets (recommended for TYPO3 v13+)

Add the site set to your site configuration (`config/sites/<identifier>/config.yaml`)
or via **Sites > Setup** in the backend:

```yaml
dependencies:
  - k3n/tonictypes
```

With Tonictypes Professional installed, add:

```yaml
dependencies:
  - k3n/tonictypes
  - k3n/tonictypes_pro
```

The professional set depends on `k3n/tonictypes` and loads its TypoScript, Page TSconfig,
and site settings automatically.

Plugin options are available under **Sites > Settings** (cache lifetime, Fluid paths, variable names).
Settings use the same keys as TypoScript constants (`plugin.tx_tonictypes.*`), so both approaches stay in sync.

List available sets:

```bash
vendor/bin/typo3 site:sets:list
```

### TypoScript static template (classic)

Include **"[Tonictypes] General Configuration"** in your root TypoScript template
(**Web > Template > Includes**). For Professional, also include
**"[Tonictypes] Tonictypes Professional"**.

### Mixed setup (site set + TypoScript template)

If you use a root `sys_template` record **and** site sets, disable **Clear constants**
and **Clear setup** on the root template. Otherwise site-set TypoScript is cleared and will not apply.

### Custom TypoScript

Tonictypes has some additional backend TypoScript configuration possibilities:

### Adding new templates

```
######################
# Tonictypes Templates
######################
plugin.tx_tonictypes.templates {
  // Article
  articleList {
    group = Article
    icon = EXT:tonictypes/Resources/Public/Icons/Datatype/blog-blue.png
    name = Article List
    file = EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/List.html
  }
  articleListHome {
    group = Article
    icon = EXT:tonictypes/Resources/Public/Icons/Datatype/blog-blue.png
    name = Article List Home
    file = EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/HomeList.html
  }
}
```

### Extending generated classes

You can extend the generated classes of your datatypes by using the extbase class mapping.

```
config.tx_extbase{
    persistence{
        objects{
            K3n\Tonictypes\Domain\Model\Record\Your\Domain\Model.className = Vendor\Namespace\Domain\Model\Your\Domain\Model
        }
    }
}
```

## Upgrade notes (2.2.0)

+ After upgrade, clear all caches
+ New free field types: Email, Phone, Number, Slug, Toggle
+ FlexForm values can be converted via `FlexFormProcessor` / `tt:format.flexFormToArray`

## Upgrade notes (2.1.0)

+ Requires PHP 8.2 or higher
+ After upgrade, run **Analyze Database Structure** and clear all caches
+ Professional-only field types require `k3n/tonictypes_pro`

## Future Roadmap

+ Provide Plugins for Search, Filter, Sorting and Pagination (Professional)
+ Add additional ViewHelpers to customize fluid templating
+ Add possibilities to customize value types
