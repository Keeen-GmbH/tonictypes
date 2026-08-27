.. include:: ../Includes.txt

.. _installation:

============
Installation
============

Requirements
============

* TYPO3 12.4 – 14.9
* PHP 8.2 – 8.5
* Composer-based TYPO3 installation (recommended)

Install Core
============

.. code-block:: bash

   composer require k3n/tonictypes

Activate the extension in the Extension Manager / Composer installers will
register it automatically in Composer mode.

Install Professional (optional)
===============================

Product page: https://t3planet.de/tonictypes

.. code-block:: bash

   composer require k3n/tonictypes_pro

Professional depends on Core, `nitsan/ns-license`, and related license
components. After install, clear all caches.

Activate configuration
======================

Site sets (recommended for TYPO3 v13+)
--------------------------------------

In :file:`config/sites/<identifier>/config.yaml` (or **Sites > Setup**):

.. code-block:: yaml

   dependencies:
     - k3n/tonictypes

With Professional:

.. code-block:: yaml

   dependencies:
     - k3n/tonictypes
     - k3n/tonictypes_pro

Plugin options (cache lifetime, Fluid paths, variable names) are available
under **Sites > Settings**. Keys match TypoScript constants
(`plugin.tx_tonictypes.*`).

List sets:

.. code-block:: bash

   vendor/bin/typo3 site:sets:list

TypoScript static template (classic)
------------------------------------

Include **[Tonictypes] General Configuration** in the root TypoScript
template (**Web > Template > Includes**).

For Professional, also include **[Tonictypes] Tonictypes Professional**.

Mixed setup warning
-------------------

If you use both a root `sys_template` and site sets, disable **Clear
constants** and **Clear setup** on the root template. Otherwise site-set
TypoScript is cleared and will not apply.

After install
=============

1. Clear all caches
2. Run **Admin Tools > Maintenance > Analyze Database Structure** if prompted
3. Open the Tonictypes backend modules and create your first field/datatype

See :ref:`getting-started` for the first project walkthrough.
