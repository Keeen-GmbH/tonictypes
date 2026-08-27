.. include:: ../Includes.txt

.. _getting-started-datatypes:

=========
Datatypes
=========

A datatype is a record type (like a mini-extension model): it owns a table,
TCA, and a set of assigned fields.

Create a datatype
=================

1. Open the Tonictypes **Datatypes** module
2. Create a new datatype
3. Set title, icon, and identifier
4. Assign previously created fields (order matters for the form)
5. Optionally enable **default hidden** so new records start disabled
6. Save

Next: create the physical table and TCA — see :ref:`getting-started-table`.

Tips
====

* Keep variable names stable; Fluid templates depend on them
* Use palettes / field order intentionally for editors
* Prefer Core field types first; add Professional types only when needed
