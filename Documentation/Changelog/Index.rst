.. include:: ../Includes.txt

.. _changelog:

=========
Changelog
=========

This chapter summarizes recent releases. Full details live in the extension
:file:`CHANGELOG.md` files of Core and Professional.

2.2.0 (Core)
============

* Add Email, Phone, Number, Slug, and Toggle field types
* Add FlexForm conversion helper for Fluid / dataProcessing
* Add readOnly option for field configuration
* Improve TYPO3 v12–v14 compatibility (frontend auth, Query Builder)
* QA / compatibility testing

2.2.0 (Professional)
====================

* Add Repeater field type (presets + custom FlexForm path)
* Add readOnly option for Professional field configuration
* Improve TYPO3 v14 compatibility and CI coverage

2.1.0
=====

* Datatype export/import transfer module in Core
* Predefined datatype import dashboard widget
* default_hidden for new records
* MCP tools in Professional
* PHP 8.2–8.5 and TYPO3 12.4–14.9 requirements
* Professional-only field types moved out of free package

Upgrade notes (2.2.0)
=====================

* Clear all caches after upgrade
* New Core field types appear in the field type selector
* Use Professional 2.2.0+ with Core 2.2.0+ when using Repeater
