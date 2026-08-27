.. include:: ../Includes.txt

.. _getting-started-templating:

=========================
Custom template creation
=========================

Tonictypes renders records with **Fluid** templates from your sitepackage.
Field values are available under each field’s **variable name**
(for example `{record.title}`).

Step 1: Create the Fluid file
=============================

Create templates in your sitepackage, for example:

.. code-block:: text

   EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/List.html
   EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/HomeList.html
   EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/Detail.html

Minimal **list** example:

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
         xmlns:tt="http://typo3.org/ns/K3n/Tonictypes/ViewHelpers"
         data-namespace-typo3-fluid="true">

   <f:for each="{records}" as="record">
     <article>
       <h2>
         <tt:link.record record="{record}" pageUid="{settings.detailPid}">
           {record.title}
         </tt:link.record>
       </h2>
       <p>{record.teaser}</p>
     </article>
   </f:for>

   </html>

Minimal **detail** example:

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
         data-namespace-typo3-fluid="true">

   <article>
     <h1>{record.title}</h1>
     <f:format.html>{record.bodytext}</f:format.html>
   </article>

   </html>

The list/detail variable names default to `records` / `record` and can be
changed via TypoScript constants
(`recordsVariableName` / `singleRecordVariableName`).

Step 2: Register templates in TypoScript
========================================

Register each template under `plugin.tx_tonictypes.templates`.
Use a clear key, a **group** (shown in the plugin selector), an **icon**,
a human **name**, and the Fluid **file** path.

Example (Tonictypes / Article templates):

.. code-block:: typoscript

   ######################
   # Tonictypes Templates
   ######################
   plugin.tx_tonictypes.templates {
     // Article
     articleList {
       group = Article
       icon = EXT:tonictypes/Resources/Public/Icons/Datatype/blog-blue.png
       name = Article List
       file = EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/List.html
     }
     articleListHome {
       group = Article
       icon = EXT:tonictypes/Resources/Public/Icons/Datatype/blog-blue.png
       name = Article List Home
       file = EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/HomeList.html
     }
     articleDetail {
       group = Article
       icon = EXT:tonictypes/Resources/Public/Icons/Datatype/blog-blue.png
       name = Article Detail
       file = EXT:my_sitepackage/Resources/Private/Templates/Extensions/Tonictypes/Article/Detail.html
     }
   }

Registration properties
-----------------------

======= ================================================================
Key     Purpose
======= ================================================================
`group` Group label in the plugin template selector
`icon`  Icon shown next to the template option
`name`  Label shown in the plugin FlexForm
`file`  Fluid template path (`EXT:…/….html`)
======= ================================================================

Put this TypoScript in your site set or a sitepackage setup file that is
loaded after Tonictypes Core, then clear caches.

Step 3: Select the template in the plugin
=========================================

1. Add a Tonictypes plugin (List, Detail, Dynamic, or Plain)
2. Configure storage page / datatype
3. Under template selection:
   * choose a **registered** template from the list, or
   * use **CUSTOM** and enter a full `EXT:…` path, or
   * use **FLUID** for inline Fluid (where offered)
4. Save the content element

Optional: override Fluid root paths
===================================

To keep all Tonictypes templates under your sitepackage roots:

.. code-block:: typoscript

   plugin.tx_tonictypes {
     view {
       templateRootPath = EXT:my_sitepackage/Resources/Private/Templates/Tonictypes/
       partialRootPath = EXT:my_sitepackage/Resources/Private/Partials/Tonictypes/
       layoutRootPath = EXT:my_sitepackage/Resources/Private/Layouts/Tonictypes/
     }
   }

Registered `file` paths can still point to any `EXT:…` location.

FlexForm / Repeater fields in templates
=======================================

Repeater and Flex fields store XML. Convert them before looping:

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
         xmlns:tt="http://typo3.org/ns/K3n/Tonictypes/ViewHelpers"
         data-namespace-typo3-fluid="true">

   <f:variable name="faq"
               value="{tt:format.flexFormToArray(flex: record.faq)}" />
   <f:for each="{faq.items}" as="item">
     <h3>{item.question}</h3>
     <p>{item.answer}</p>
   </f:for>

   </html>

See :ref:`viewhelpers` for all frontend helpers (`tt:link.record`,
`tt:uri.record`, filter/group helpers, …).
