.. include:: ../Includes.txt

.. _transfer:

=========================
Export / Import (Transfer)
=========================

From Tonictypes **2.1.0**, datatype export/import lives in **Core**
(**System > Export / Import**).

What is transferred
===================

A transfer archive can include:

* Datatype metadata
* Assigned fields and field values / variables
* Related configuration needed to recreate the structure

Premium field types (`repeater`, `inline`, `flex`, …) require
`tonictypes_pro` on the target instance. Core blocks importing archives that
contain unavailable premium types.

Workflow
========

Export
------

1. Open **System > Export / Import**
2. Select datatype(s)
3. Download the archive

Import
------

1. Upload the archive
2. Review the import preview (create vs update mapping)
3. Confirm import
4. Create/update tables and clear caches as needed

Predefined datatype widget
==========================

The dashboard widget **Predefined Datatype Import** can load bundled sample
structures for quick demos.
