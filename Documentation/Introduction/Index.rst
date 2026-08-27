.. include:: ../Includes.txt

.. _introduction:

============
Introduction
============

Tonictypes is the evolution of the proven DataViewer / Typotonic approach:
build custom record types directly in the TYPO3 backend, powered by real TCA,
without packaging a new extension for every project need.

.. figure:: ../Images/Extension.svg
   :alt: Tonictypes extension icon
   :width: 96

What you can do
===============

* Create reusable **fields** (input, select, image, RTE, email, slug, …)
* Combine fields into a **datatype** (for example News, Job, Event)
* Generate the **database table**, TCA, and optional Extbase classes
* Create **records** in the TYPO3 list module
* Render them with **Fluid** and frontend **plugins**
* Export/import datatype structures between instances
* (Professional) use advanced field types and **MCP** tools for agent workflows

Typical workflow
================

1. Create fields (with field values where needed)
2. Create a datatype and assign fields
3. Create / update the record table and TCA
4. Create records
5. Build Fluid templates (list / detail)
6. Place a Tonictypes plugin on a page

Core vs Professional
====================

==================== ===========================================================
Package              Role
==================== ===========================================================
`k3n/tonictypes`     Free Core: fields, datatypes, plugins, transfer, templating
`k3n/tonictypes_pro` Paid add-on: advanced fields, toolbar, DocHeader, MCP tools
==================== ===========================================================

Professional requires Core and is licensed via `nitsan/ns-license`.
MCP tooling is available through the Pro stack (`ns_t3af` / Pro); it is not
part of the free package.

Product / shop (Professional): https://t3planet.de/tonictypes

Compatibility
=============

* TYPO3 CMS **12.4 – 14.9**
* PHP **8.2 – 8.5**

History
=======

Tonictypes continues the idea popularized by DataViewer: one maintainable
extension instead of many mini-extensions, so upgrades stay simpler across
TYPO3 major versions. See also the upstream concept in
`magedeveloper/dataviewer <https://github.com/magedeveloper/dataviewer>`__.

Further links
=============

* Product / Professional: https://t3planet.de/tonictypes
* Product site: https://www.tonictypes.com
* Packagist: https://packagist.org/packages/k3n/tonictypes
* TYPO3 docs hub: https://docs.typo3.org/
