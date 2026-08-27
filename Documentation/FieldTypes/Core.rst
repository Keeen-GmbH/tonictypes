.. include:: ../Includes.txt

.. _field-types-core:

================
Core field types
================

Textual
=======

Input
-----

Standard single-line TCA input. Supports eval options, placeholders, and
optional value picker items from field values.

Email
-----

Uses TCA `type=email` with trim. Prefer this over a plain input when you need
email validation in FormEngine.

Phone
-----

Input with `PhoneNumberEvaluation` (normalize whitespace, basic character
checks). Placeholder can be customized (default example: `+49 123 456789`).

Number
------

TCA `type=number` with:

* Format: **integer** or **decimal**
* Optional **minimum** / **maximum**
* Optional **slider** (+ step)
* Default value

SQL defaults to `int(11)` or `double(11,2)` depending on format.

Slug
----

TCA `type=slug` generated from one or more source fields (default `title`).

Configurable:

* Source fields (comma-separated field codes)
* Field separator / fallback character
* Uniqueness: `uniqueInPid` (default), `unique`, or `uniqueInSite`

Textarea / Editor / RTE / Table
-------------------------------

* **textarea** — multi-line text
* **editor** — code editor (`t3editor` / `codeEditor` depending on TYPO3 version)
* **rte** — rich text
* **table** — table wizard

Selection
=========

* **select** / **multiselect** — items from field values and/or `foreign_table`
* **radio** / **checkbox** — classic option groups
* **toggle** — `checkboxToggle` or labeled Yes/No toggle
* **page** / **group** / **folder** / **tree** — TYPO3 relation UIs

Date and media
==============

* **date** — date picker
* **datetime** — date and time picker
* **link** — link wizard
* **image** / **relation** — FAL
* **colorpicker**

Toggle details
==============

Toggle options:

* Show Yes/No labels (`checkboxLabeledToggle`)
* Labels for checked/unchecked
* Default ON
* Invert display state
