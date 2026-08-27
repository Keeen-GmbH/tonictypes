.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

Site sets
=========

See :ref:`installation` for adding `k3n/tonictypes` and
`k3n/tonictypes_pro` as site dependencies.

Site settings use the same keys as TypoScript constants under
`plugin.tx_tonictypes.*` (view paths, cache lifetime, variable names).

TypoScript constants (examples)
===============================

.. code-block:: typoscript

   plugin.tx_tonictypes {
     view {
       templateRootPath = EXT:my_sitepackage/Resources/Private/Templates/Tonictypes/
       partialRootPath = EXT:my_sitepackage/Resources/Private/Partials/Tonictypes/
       layoutRootPath = EXT:my_sitepackage/Resources/Private/Layouts/Tonictypes/
     }
     developer {
       cache_lifetime = 3600
       recordsVariableName = records
       singleRecordVariableName = record
     }
   }

Custom frontend templates
=========================

Register Fluid templates for plugins (see :ref:`getting-started-templating`
for the full walkthrough):

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
   }

Field type registration
=======================

See :ref:`field-types-custom`.

Extbase class mapping
=====================

Override generated domain models:

.. code-block:: typoscript

   config.tx_extbase.persistence.objects {
     K3n\Tonictypes\Domain\Model\Record\News\News.className =
       Vendor\Sitepackage\Domain\Model\News
   }

Page TSconfig (Professional DocHeader)
======================================

Show “new record” DocHeader buttons for selected datatype UIDs:

.. code-block:: typoscript

   tx_tonictypes.docHeaderDatatypes = 12,15,18

(Requires `tonictypes_pro`.)
