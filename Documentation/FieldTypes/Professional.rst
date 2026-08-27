.. include:: ../Includes.txt

.. _field-types-professional:

========================
Professional field types
========================

These types are registered by `EXT:tonictypes_pro`. They are blocked on
datatype import into Core-only instances.

Repeater
========

Repeatable FlexForm **section** storage (mediumtext XML), ideal for FAQ,
highlights, icon+text rows, and similar structures.

Presets
-------

* Highlight
* FAQ
* Icon + Text
* **Custom** — provide a `FILE:EXT:…/MyRepeater.xml` data structure path

Frontend usage
--------------

Convert XML with the Core ViewHelper or `FlexFormProcessor`:

.. code-block:: html

   <html xmlns:tt="http://typo3.org/ns/K3n/Tonictypes/ViewHelpers"
         data-namespace-typo3-fluid="true">
   <f:variable name="repeater"
               value="{tt:format.flexFormToArray(flex: record.faq)}" />
   <f:for each="{repeater.items}" as="item">
     <h3>{item.question}</h3>
     <p>{item.answer}</p>
   </f:for>
   </html>

DynamicInput
============

FlexForm-based dynamic input presets (input / link / text DS variants).

Flex / Inline / Datatype / Content
==================================

* **flex** — attach an arbitrary FlexForm DS
* **inline** — IRRE-style inline records
* **datatype** — relate to other Tonictypes datatypes
* **content** — content element references

Fluid / User / TCA / PassThrough
================================

* **fluid** — render a Fluid template as a backend field UI
* **user** — custom `type=user` / renderType integration
* **tca** — inject raw TCA XML
* **passthrough** — store values without a visible editor widget

See also :ref:`professional` for toolbar, DocHeader, and MCP.
