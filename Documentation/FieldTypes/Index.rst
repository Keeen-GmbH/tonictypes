.. include:: ../Includes.txt

.. _field-types:

===========
Field types
===========

Tonictypes ships preconfigured TCA field types. Choose a type when creating a
field; configure options in the field FlexForm.

.. toctree::
   :maxdepth: 1
   :titlesonly:

   Core
   Professional
   Custom

Overview
========

Core (`tonictypes`)
-------------------

================== ===========================================================
Type key           Description
================== ===========================================================
`input`            Single-line text
`email`            Validated email (TCA `type=email`)
`phone`            Phone input with formatting/validation
`number`           Integer or decimal with optional min/max and slider
`slug`             URL slug generated from source fields
`textarea`         Multi-line text
`select`           Single select
`multiselect`      Multi select
`radio`            Radio buttons
`checkbox`         Checkboxes
`toggle`           Yes/No toggle switch
`date`             Date
`datetime`         Date and time
`rte`              Rich text editor
`table`            Table wizard
`editor`           Code editor
`link`             Link browser
`image`            Image / FAL images
`page`             Page selection
`tree`             Category / tree selection
`group`            Group relations
`folder`           Folder selection
`relation`         File relation
`colorpicker`      Color picker
================== ===========================================================

Professional (`tonictypes_pro`)
-------------------------------

================== ===========================================================
Type key           Description
================== ===========================================================
`repeater`         Repeatable FlexForm sections (FAQ, highlight, …)
`dyninput`         Dynamic input FlexForm presets
`flex`             Custom FlexForm DS
`inline`           Inline IRRE relations
`datatype`         Relation to other Tonictypes datatypes
`content`          Content element relation
`fluid`            Fluid-rendered backend field
`user`             Custom userFunc / renderType field
`tca`              Raw TCA XML injection
`passthrough`      Pass-through / non-editable storage
================== ===========================================================

Common options
==============

Most editable types support:

* `required`, `nullable`, `readOnly`
* size / max where applicable
* type-specific options (range, source fields, foreign table, …)

Display conditions and request-update are configured on the field record
itself (not only inside the type FlexForm).
