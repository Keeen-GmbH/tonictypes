.. include:: ../Includes.txt

.. _getting-started-table:

=====================
Records and DB table
=====================

After fields are assigned to a datatype, create or update the database table
and TCA so editors can create records.

Table status / migrate
======================

In the datatype tools / table UI:

1. Check **table status** (missing columns, changes, orphans)
2. Run **create / update table** to apply safe schema changes
3. Generate **TCA** if needed
4. Optionally generate Extbase **model/repository** classes

Clear caches after schema or TCA changes.

.. important::

   Prefer adding columns over dropping them on production data. Orphan column
   removal is a separate, confirmed action in the UI.

Create records
==============

Once the table exists:

1. Open the storage page in the page tree
2. Use the list module for
   `tx_tonictypes_domain_model_record_<datatype>`
3. Create and publish records as usual (languages, access, …)

Default hidden
==============

If the datatype has **default hidden** enabled, new records start with
`hidden=1` until an editor enables them.
