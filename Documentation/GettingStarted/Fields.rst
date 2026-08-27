.. include:: ../Includes.txt

.. _getting-started-fields:

======
Fields
======

Fields are reusable building blocks. You create them once and assign them to
one or more datatypes.

Create a field
==============

1. Open the Tonictypes **Fields** module
2. Create a new field
3. Set:
   * **Frontend label** — backend form label
   * **Variable name** — Fluid/variable identifier (for example `title`)
   * **Type** — see :ref:`field-types`
4. Configure type-specific options in the field FlexForm
5. Add **field values** when the type needs selectable options (select, radio, …)
6. Save

Useful field options
====================

===================== ========================================================
Option                Purpose
===================== ========================================================
Exclude               Respect backend user exclude fields
Request update        Reload form on change (`onChange=reload`)
Display condition     TCA `displayCond` (show/hide based on other fields)
Validation            Extra eval / validation rules
Database type         Override generated SQL column type
Read only             Render the field as read-only in FormEngine
===================== ========================================================

.. tip::

   Display conditions already exist on every field. Use the **Display
   condition** tab with TCA syntax, for example
   `FIELD:layout:=:image`.

Field values
============

Depending on the type, field values can be:

* Fixed label|value pairs
* Database lookups
* TypoScript-generated values

They feed select/radio/checkbox options and defaults.
