.. include:: ../Includes.txt

.. _viewhelpers:

===========
ViewHelpers
===========

Use these Fluid ViewHelpers in **frontend templates** when working with
Tonictypes records.

Register the namespace:

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
         xmlns:tt="http://typo3.org/ns/K3n/Tonictypes/ViewHelpers"
         data-namespace-typo3-fluid="true">

.. note::

   Backend-only ViewHelpers used internally by Tonictypes modules are not
   documented here.

Overview
========

=============================== ===============================================
ViewHelper                      Purpose
=============================== ===============================================
`tt:link.record`                HTML link to a record detail
`tt:uri.record`                 URI only for a record detail
`tt:record.get`                 Load one record by UID + datatype
`tt:datatype.get`               Load one datatype by UID
`tt:filter.records`             Filter records (rule-based)
`tt:group.recordsByProperty`    Group records by field value
`tt:format.flexFormToArray`     FlexForm / Repeater XML → array
`tt:format.xmlToArray`          Generic XML → array
`tt:string.explode`             Explode a string to array
`tt:string.codeFromString`      Build a code/identifier from a string
`tt:array.keyValuePair`         Build a one-key array
`tt:template.render`            Render an extra Fluid template file
`tt:typo3.isVersion`            Check current TYPO3 major version
=============================== ===============================================

Record links
============

`tt:link.record`
----------------

Renders an ``<a>`` tag to a record detail view.

Defaults: plugin `Dynamic`, action `dynamicDetail`, controller `Record`.

================== ======== ==================================================
Argument           Required Description / default
================== ======== ==================================================
`record`           yes      Record object
`action`           no       Default `dynamicDetail`
`controller`       no       Default `Record`
`pluginName`       no       Default `Dynamic`
`extensionName`    no       Default `Tonictypes`
`pageUid`          no       Detail page UID
`arguments`        no       Extra controller arguments
`absolute`         no       Absolute URL
`section`          no       Anchor
`additionalParams` no       Extra query parameters
================== ======== ==================================================

.. code-block:: html

   <tt:link.record record="{record}" pageUid="{settings.detailPid}">
     {record.title}
   </tt:link.record>

`tt:uri.record`
---------------

Same routing defaults as `tt:link.record`, but returns only the URI string.

================== ======== ==================================================
Argument           Required Description / default
================== ======== ==================================================
`record`           yes      Record object
`action`           no       Default `dynamicDetail`
`controller`       no       Default `Record`
`pluginName`       no       Default `Dynamic`
`extensionName`    no       Default `Tonictypes`
`pageUid`          no       Detail page UID
`arguments`        no       Extra arguments
`absolute`         no       Absolute URI
================== ======== ==================================================

.. code-block:: html

   <a href="{tt:uri.record(record: record, pageUid: settings.detailPid)}">
     {record.title}
   </a>

Load records / datatypes
========================

`tt:record.get`
---------------

================== ======== ==================================================
Argument           Required Description / default
================== ======== ==================================================
`uid`              yes      Record UID
`datatype`         yes      Datatype model object
`onlyEnabled`      no       Default `true`
================== ======== ==================================================

.. code-block:: html

   <f:variable name="item"
               value="{tt:record.get(uid: 12, datatype: datatype)}" />

`tt:datatype.get`
-----------------

================== ======== ==================================================
Argument           Required Description / default
================== ======== ==================================================
`uid`              yes      Datatype UID
`onlyEnabled`      no       Default `true`
================== ======== ==================================================

.. code-block:: html

   <f:variable name="datatype" value="{tt:datatype.get(uid: 3)}" />

Filter and group
================

`tt:filter.records`
-------------------

Filters a `QueryResult`, or loads filtered records from a datatype repository.

Provide either `records` or `datatype`.

===================== ======== ================================================
Argument              Required Description / default
===================== ======== ================================================
`records`             no       Existing `QueryResult`
`datatype`            no       Datatype (or UID) to query
`filters`             no       Filter array (`condition` + `rules`)
`variables`           no       Variables for filter processing
`respectStoragePage`  no       Default `true`
`storagePageIds`      no       Storage PIDs
`ignoreEnableFields`  no       Default `false`
===================== ======== ================================================

.. code-block:: html

   <f:variable name="filtered" value="{tt:filter.records(
     records: records,
     filters: {
       condition: 'AND',
       rules: {
         0: {field: 'title', operator: 'contains', value: 'sales'}
       }
     }
   )}" />

`tt:group.recordsByProperty`
----------------------------

==================== ======== ================================================
Argument             Required Description / default
==================== ======== ================================================
`records`            yes      Iterator / QueryResult / array
`property`           yes      Field / property name
`returnOnlyGroups`   no       Return only group keys (`false`)
`multiple`           no       Split comma-separated values (`false`)
==================== ======== ================================================

.. code-block:: html

   <f:variable name="grouped"
               value="{tt:group.recordsByProperty(records: records, property: 'category')}" />
   <f:for each="{grouped}" as="items" key="category">
     <h2>{category}</h2>
     <f:for each="{items}" as="record">{record.title}</f:for>
   </f:for>

FlexForm / Repeater values
==========================

`tt:format.flexFormToArray`
--------------------------

Converts FlexForm XML (including Repeater sections) into a plain array.

================== ======== ==================================================
Argument           Required Description
================== ======== ==================================================
`flex`             yes      FlexForm XML string
`languagePointer`  no       Default `lDEF`
`valuePointer`     no       Default `vDEF`
================== ======== ==================================================

Section items are returned as a numeric list (for example `items.0.title`).

.. code-block:: html

   <f:variable name="faq"
               value="{tt:format.flexFormToArray(flex: record.faq)}" />
   <f:for each="{faq.items}" as="item">
     <h3>{item.question}</h3>
     <p>{item.answer}</p>
   </f:for>

For ``tt_content`` / CONTENT `dataProcessing` contexts you can also use
`K3n\Tonictypes\DataProcessing\FlexFormProcessor`. For Extbase record
properties, prefer this ViewHelper.

`tt:format.xmlToArray`
----------------------

================== ======== ==================================================
Argument           Required Description
================== ======== ==================================================
`xml`              yes      XML input
================== ======== ==================================================

Helpers
=======

`tt:string.explode`
-------------------

==================== ======== ================================================
Argument             Required Description / default
==================== ======== ================================================
`string`             yes      Input string
`delimeter`          no       Delimiter (default `,`) — spelling as in API
`removeEmptyValues`  no       Default `true`
`limit`              no       Default `0`
==================== ======== ================================================

`tt:string.codeFromString`
--------------------------

================== ======== ==================================================
Argument           Required Description
================== ======== ==================================================
`string`           yes      Source string
================== ======== ==================================================

`tt:array.keyValuePair`
-----------------------

Returns `[key => value]`.

================== ======== ==================================================
Argument           Required Description
================== ======== ==================================================
`key`              yes      Array key
`value`            yes      Array value
================== ======== ==================================================

`tt:template.render`
--------------------

Renders an additional Fluid template file (optional Tonictypes variables + cache).

================== ======== ==================================================
Argument           Required Description / default
================== ======== ==================================================
`template`         yes      Template file path
`arguments`        no       Variables for the template
`variables`        no       Tonictypes variable UIDs to inject
`cache`            no       Enable cache (`false`)
`lifetime`         no       Cache lifetime
`cacheIdentifier`  no       Custom cache id
`pid`              no       Page id context
================== ======== ==================================================

.. code-block:: html

   <tt:template.render
     template="EXT:my_site/Resources/Private/Templates/Box.html"
     arguments="{title: record.title}" />

`tt:typo3.isVersion`
--------------------

================== ======== ==================================================
Argument           Required Description
================== ======== ==================================================
`major`            yes      Major version (e.g. `13`)
================== ======== ==================================================

.. code-block:: html

   <f:if condition="{tt:typo3.isVersion(major: 13)}">
     <p>Running on TYPO3 v13</p>
   </f:if>
