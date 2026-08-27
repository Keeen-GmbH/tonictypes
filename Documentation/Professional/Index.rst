.. include:: ../Includes.txt

.. _professional:

========================
Tonictypes Professional
========================

`k3n/tonictypes_pro` extends Core with advanced backend UX, field types, and
agent/MCP tooling.

**Get Professional:** https://t3planet.de/tonictypes

.. toctree::
   :maxdepth: 1
   :titlesonly:

   Mcp

Requirements
============

* `k3n/tonictypes` 2.x (2.2.0+ recommended)
* TYPO3 12.4 – 14.9 / PHP 8.2 – 8.5
* License stack (`nitsan/ns-license`, …)

Install
=======

Purchase / product page: https://t3planet.de/tonictypes

.. code-block:: bash

   composer require k3n/tonictypes_pro

Add site set dependency `k3n/tonictypes_pro` or include the Professional
static TypoScript template.

Feature overview
================

* Backend **toolbar item** for recent/new records
* **DocHeader** “add record” buttons for selected datatypes
* Advanced field types (Repeater, DynamicInput, Inline, Flex, Datatype,
  Content, Fluid, User, TCA, PassThrough)
* **MCP tools** for datatype/field/record management (via Pro + `ns_t3af`)
* Removes Core “Buy Professional” notices

Transfer note
=============

Datatype export/import itself is a **Core** module since 2.1.0. Professional
is still required on the target system when archives contain Pro field types.

Field types
===========

Detailed descriptions: :ref:`field-types-professional`.
