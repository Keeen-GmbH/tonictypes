.. include:: ../Includes.txt

.. _plugins:

=======
Plugins
=======

Tonictypes registers frontend plugins as content elements in the
**Tonictypes** wizard group.

Available plugins
=================

============= ================================================================
Plugin        Purpose
============= ================================================================
**List**      List records from selected datatype(s) / storage
**Detail**    Single record detail view
**Dynamic**   Flexible/dynamic record display selection
**Plain**     Lightweight/plain rendering mode
============= ================================================================

CTypes look like `tonictypes_list`, `tonictypes_detail`, …

Usage
=====

1. Include Tonictypes TypoScript / site set
2. Create a content element of the desired plugin type
3. Select **Record Storage Page** / datatype and related FlexForm options
4. Save once after choosing storage so dependent selects can reload
5. Choose a Fluid template registered under `plugin.tx_tonictypes.templates`

.. note::

   After changing storage pages or datatype, save the plugin so FlexForm
   select options refresh.

Plugin FlexForms
================

Each plugin has its own FlexForm under
:file:`EXT:tonictypes/Configuration/FlexForms/Plugins/`.

Typical options include:

* Datatype selection
* Starting point / storage pages
* Template selection (registered templates or custom path)
* Limit, sorting, pagination-related settings (where available)
* Cache lifetime related settings via constants/site settings

Compared to legacy DataViewer
=============================

Older DataViewer shipped many specialized plugins (search, letter, sort,
filter, form, pager). Tonictypes Core focuses on **List / Detail / Dynamic /
Plain**. Advanced search/filter/sort plugins may appear later as Professional
roadmap items.
